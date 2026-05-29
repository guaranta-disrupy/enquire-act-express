<?php

namespace App\AI\Schemas\App\Questionnaire;

use App\AI\Schemas\Contracts\SchemaInterface;
use App\Services\AiUsagePolicy;
use Illuminate\Support\Facades\Auth;
use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

class UpdateQuestionnaireSchema implements SchemaInterface
{
    public function getSchema(): ObjectSchema
    {
        $user = Auth::user();
        $limit = AiUsagePolicy::getActionLimit($user);
        
        return new ObjectSchema(
            name: 'update_questionnaire',
            description: 'Update questionnaire styles (CSS variables). Use this when the user wants to change visual styling properties like font family, colors, sizes, margins, etc.',
            properties: [
                new StringSchema(
                    name: 'survey_code',
                    description: 'CRITICAL: You MUST use the questionnaire_code value that is provided in the context/parameters. DO NOT generate or infer this - copy the exact questionnaire_code value provided.'
                ),
                new ArraySchema(
                    name: 'style_updates',
                    description: 'Array of style updates. Each update contains a CSS variable name (e.g., "--font-family") and its new value. Map user-friendly labels to CSS variables: "Font Family" -> "--font-family", "Font Size" -> "--header-font-size" (if for header) or "--target-font-size" (if for targets) or "--prime-font-size" (if for primes) or "--bottom-text-font-size" (if for footer), "Background Color" -> "--target-bg-color" (if for targets) or "--prime-bg-color" (if for primes), etc. CRITICAL PRIME/TARGET MAPPING: When the user mentions "primes" or "prime", use "--prime-" prefix (e.g., "--prime-font-size", "--prime-bg-color"). When the user mentions "targets" or "target", use "--target-" prefix (e.g., "--target-font-size", "--target-bg-color"). CRITICAL: For footer-related styles, use "--bottom-text-" prefix: "Footer Font Weight" -> "--bottom-text-font-weight", "Footer Font Size" -> "--bottom-text-font-size", "Footer Font Color" -> "--bottom-text-color", "Footer Font Style" -> "--bottom-text-font-style", "Footer Line Height" -> "--bottom-text-line-height", "Footer Width" -> "--bottom-text-width". Include ALL style changes the user requests.',
                    items: new ObjectSchema(
                        name: 'style_update',
                        description: 'A single style update operation',
                        properties: [
                            new StringSchema(
                                name: 'variable',
                                description: 'The CSS variable name (e.g., "--font-family", "--header-font-size", "--target-bg-color", "--prime-font-size"). Must start with "--". Map from user-friendly labels to CSS variables: "Font Family" -> "--font-family", "Font Size" -> "--header-font-size" (if for header) or "--target-font-size" (if for targets) or "--prime-font-size" (if for primes) or "--bottom-text-font-size" (if for footer), "Background Color" -> "--target-bg-color" (if for targets) or "--prime-bg-color" (if for primes), etc. CRITICAL PRIME/TARGET MAPPING: When the user mentions "primes" or "prime", ALWAYS use "--prime-" prefix (e.g., "font size of primes" -> "--prime-font-size", "background color of primes" -> "--prime-bg-color"). When the user mentions "targets" or "target", ALWAYS use "--target-" prefix (e.g., "font size of targets" -> "--target-font-size", "background color of targets" -> "--target-bg-color"). IMPORTANT FOOTER MAPPINGS: "Footer Text" or "Footer" -> use "--bottom-text-" prefix. Examples: "Footer Font Weight" -> "--bottom-text-font-weight", "Footer Font Size" -> "--bottom-text-font-size", "Footer Font Color" -> "--bottom-text-color", "Footer Font Style" -> "--bottom-text-font-style", "Footer Line Height" -> "--bottom-text-line-height", "Footer Width" -> "--bottom-text-width".'
                            ),
                            new StringSchema(
                                name: 'value',
                                description: 'The new value for the CSS variable. For font families, use standard names like "Arial", "Times New Roman", "Helvetica", etc. For colors, use hex codes like "#FF0000" or color names. For sizes (font sizes, widths, heights, margins, paddings, etc.), ALWAYS use "vmin" as the default unit when the user does not specify a unit. Examples: "5vmin", "10vmin", "2.5vmin". If the user explicitly specifies a different unit (px, rem, %, etc.), use that unit. But if no unit is specified, default to "vmin". For other properties, use appropriate CSS values.'
                            ),
                            new StringSchema(
                                name: 'theme',
                                description: 'Which theme to apply the style to. Use "1" for theme 1, "2" for theme 2, "3" for theme 3. If not specified by user, always use "1" as the default value.',
                                nullable: false
                            ),
                            new EnumSchema(
                                name: 'orientation',
                                description: 'Which orientation to apply the style to. CRITICAL DEVICE MAPPING: When the user mentions "mobile" or "mobile setup", ALWAYS use "portrait". When the user mentions "desktop" or "desktop setup", ALWAYS use "landscape". When the user mentions "tablet", "tab", "tablet setup", or "tab setup", ALWAYS use "tabletPortrait" (tablets use tabletPortrait orientation by default). Use "landscape" for desktop/landscape views, "portrait" for mobile/tablet portrait, "tabletPortrait" for tablet-specific portrait, or "all" to apply to all orientations. If not specified by user, use "all". IMPORTANT: If the user explicitly mentions a device (e.g., "in mobile setup", "for desktop", "on tablet", "in tab setup"), you MUST map it correctly: "mobile"/"mobile setup" -> "portrait", "desktop"/"desktop setup" -> "landscape", "tablet"/"tab"/"tablet setup"/"tab setup" -> "tabletPortrait".',
                                options: ['landscape', 'portrait', 'tabletPortrait', 'all']
                            ),
                        ],
                        requiredFields: ['variable', 'value', 'theme', 'orientation']
                    )
                ),
                new StringSchema(
                    name: 'message',
                    description: 'Generate a user-friendly confirmation message about what styles will be updated. Use natural language that describes the change in a clear, non-technical way. Examples: "Change font family to Arial?" or "Update header font size to 18vmin and target background color to orange?" or "Background color of primes updated to orange?". Use phrases like "Background color of primes" instead of technical CSS variable names like "--prime-bg-color". Always include the unit (vmin) in the confirmation message for size-related changes. Include what is being changed. End with a question mark.',
                    nullable: false
                ),
            ],
            requiredFields: ['survey_code', 'style_updates', 'message']
        );
    }
}