// Bestellando - Waiter Interface JavaScript
// This file can be used for JavaScript functions shared across
// different parts of the waiter interface.

// Example:
// function showGlobalWaiterNotification(message, type = 'info') {
//   // Code to display a global notification
//   console.log(`Waiter Notification (${type}): ${message}`);
// }

// Specific page logic, especially if it relies heavily on PHP-generated data
// like CSRF tokens or initial state, might be better placed in script tags
// directly within the PHP view files (as done for order/index.php),
// or by passing data to JS functions defined here.

document.addEventListener('DOMContentLoaded', function() {
    console.log('Waiter global JS loaded.');

    // Add any global event listeners or setup functions for the waiter interface here.
});
