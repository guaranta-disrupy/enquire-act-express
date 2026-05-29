<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Inertia\Inertia;
use Throwable;
use Illuminate\Database\QueryException;
use PDOException;
use Illuminate\Support\Facades\Log;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        // Handle database connection errors and 404 errors
        $this->renderable(function (Throwable $e, $request) {
            // Check if this is a database connection error
            if ($this->isDatabaseConnectionError($e)) {
                Log::error('Database connection error: ' . $e->getMessage());
                
                // If it's an API request, return JSON error
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'error' => 'Database connection failed',
                        'message' => 'The system is temporarily unavailable. Please try again later.',
                        'retry_url' => $request->fullUrl()
                    ], 503);
                }
                
                // For web requests, redirect to database error page
                // Check for survey-test in the path more flexibly
                $path = $request->path();
                if (str_contains($path, 'survey-test') || str_contains($request->fullUrl(), 'survey-test')) {
                    return \Inertia\Inertia::render('Engine/DatabaseError', [
                        'error' => 'Database connection failed',
                        'message' => 'The survey system is temporarily unavailable. Please try again later.',
                        'retryUrl' => $request->fullUrl()
                    ]);
                }
                
                // For other pages, show a simple error page
                return response()->view('errors.database', [
                    'error' => 'Database connection failed',
                    'message' => 'The system is temporarily unavailable. Please try again later.',
                    'retryUrl' => $request->fullUrl()
                ], 503);
            }
        });
    }

    /**
     * Check if the exception is a database connection error
     */
    private function isDatabaseConnectionError(Throwable $e): bool
    {
        $errorMessage = $e->getMessage();
        
        // Check for common database connection error patterns
        $databaseErrorPatterns = [
            'getaddrinfo',
            'Connection refused',
            'Connection timed out',
            'SQLSTATE[HY000] [2002]',
            'SQLSTATE[HY000] [2003]',
            'SQLSTATE[HY000] [2006]',
            'SQLSTATE[HY000] [2013]',
            'SQLSTATE[HY000] [2054]',
            'No such file or directory',
            'Connection failed',
            'Unable to connect',
            'php_network_getaddresses'
        ];
        
        foreach ($databaseErrorPatterns as $pattern) {
            if (str_contains($errorMessage, $pattern)) {
                return true;
            }
        }
        
        // Check for specific exception types
        if ($e instanceof QueryException || $e instanceof PDOException) {
            return true;
        }
        
        return false;
    }
}
