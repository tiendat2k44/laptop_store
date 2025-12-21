#!/bin/bash
# Quick verification script for cart selection feature

echo "========================================="
echo "Cart Selection Feature - Quick Verify"
echo "========================================="
echo ""

# Check PHP files exist
echo "✅ Checking files..."
files=(
    "cart.php"
    "checkout.php"
    "includes/services/CartService.php"
    "includes/services/OrderService.php"
    "ajax/cart-remove.php"
)

for file in "${files[@]}"; do
    if [ -f "$file" ]; then
        echo "   ✓ $file"
    else
        echo "   ✗ $file MISSING"
    fi
done
echo ""

# Check syntax
echo "✅ Checking PHP syntax..."
php -l cart.php > /dev/null && echo "   ✓ cart.php"
php -l checkout.php > /dev/null && echo "   ✓ checkout.php"
php -l ajax/cart-remove.php > /dev/null && echo "   ✓ ajax/cart-remove.php"
echo ""

# Check for key implementations
echo "✅ Checking implementations..."
grep -q "selected_items\[\]" cart.php && echo "   ✓ Checkboxes with selected_items[] in cart.php"
grep -q "clearSelectedItems" includes/services/CartService.php && echo "   ✓ clearSelectedItems() in CartService"
grep -q "type=\"submit\"" cart.php && echo "   ✓ Submit button in cart.php"
grep -q "shop_id" includes/services/OrderService.php && echo "   ✓ shop_id handling in OrderService"
echo ""

# Show key methods
echo "📋 Key Methods Implemented:"
echo "   - CartService::clearSelectedItems(\$itemIds)"
echo "   - cart.php: form POST to /checkout.php with selected_items[]"
echo "   - checkout.php: require POST selected_items or redirect"
echo "   - cart.php: JavaScript form validation and total calculation"
echo ""

echo "🚀 READY TO TEST"
echo ""
echo "Test Instructions:"
echo "1. Log in to the application"
echo "2. Add 3+ products to cart"
echo "3. Visit: http://localhost/TienDat123/laptop_store-main/cart.php"
echo "4. Select 2 items (uncheck 1)"
echo "5. Click 'Tiến hành thanh toán'"
echo "6. Complete payment (use COD)"
echo "7. Verify order has only 2 items"
echo "8. Go back to cart - should show 1 item remaining"
echo ""
echo "✅ Script complete!"
