#!/bin/bash

# Frontend Error Management Workflow Script

echo "======================================"
echo "   Frontend Error Management"
echo "======================================"
echo ""

# View current errors
php artisan errors:frontend view

echo ""
echo "======================================"
echo ""

# Ask if user wants to clear/archive
read -p "Have you fixed the errors? (yes/no): " response

if [ "$response" = "yes" ] || [ "$response" = "y" ]; then
    read -p "Do you want to (1) Clear or (2) Archive? Enter 1 or 2: " action
    
    if [ "$action" = "1" ]; then
        php artisan errors:frontend clear --no-interaction
        echo ""
        echo "✅ Errors cleared! Waiting for next errors..."
    elif [ "$action" = "2" ]; then
        php artisan errors:frontend archive --no-interaction
        echo ""
        echo "✅ Errors archived and cleared! Waiting for next errors..."
    else
        echo "Invalid choice. Exiting."
    fi
else
    echo "Keep working on the errors. Run this script again when fixed."
fi

echo ""
