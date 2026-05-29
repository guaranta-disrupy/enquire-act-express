#!/bin/bash

# Worker 1: odd group questions
# Worker 2: even group questions
# Worker 3: system messages

php artisan queue:work database --queue=translations --tries=3 --timeout=300 --name=translation-worker-odd &
WORKER1_PID=$!
 
php artisan queue:work database --queue=translations --tries=3 --timeout=300 --name=translation-worker-even &
WORKER2_PID=$!

php artisan queue:work database --queue=translations --tries=3 --timeout=300 --name=translation-worker-system &
WORKER3_PID=$!


echo "Workers started with PIDs: $WORKER1_PID, $WORKER2_PID, $WORKER3_PID"

# Function to cleanup on exit
cleanup() {
    echo "Stopping workers..."
    kill $WORKER1_PID 2>/dev/null
    kill $WORKER2_PID 2>/dev/null
    kill $WORKER3_PID 2>/dev/null
    echo "Workers stopped"
    exit 0
}

trap cleanup SIGINT SIGTERM

wait
