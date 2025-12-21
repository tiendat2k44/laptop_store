#!/bin/bash
# Test cart selection flow

echo "========================================="
echo "Cart Selection Flow - Quick Test"
echo "========================================="
echo ""

SITE_URL="http://localhost/TienDat123/laptop_store-main"

echo "1️⃣  Cart Page:"
echo "   URL: $SITE_URL/cart.php"
echo ""

echo "2️⃣  Things to check:"
echo "   ✓ Open F12 (DevTools)"
echo "   ✓ Go to Console tab"
echo "   ✓ Open cart.php"
echo "   ✓ Select 1-2 products (check checkbox)"
echo "   ✓ Verify console shows:"
echo "     - 'Total checkboxes: X'"
echo "     - 'Checked checkboxes: Y' (Y >= 1)"
echo ""

echo "3️⃣  Click 'Tiến hành thanh toán' button"
echo "   Watch console for:"
echo "   ✓ 'Form submit event triggered'"
echo "   ✓ 'Form will submit with X items'"
echo ""

echo "4️⃣  Check Network tab:"
echo "   ✓ Look for POST /checkout.php request"
echo "   ✓ View Request Body (Payload)"
echo "   ✓ Should contain: selected_items[]: 123"
echo ""

echo "5️⃣  If still getting error:"
echo "   Run in console:"
echo "   document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = true);"
echo "   Then submit again"
echo ""

echo "6️⃣  Test with debug page:"
echo "   URL: $SITE_URL/diagnostics/debug_post.php"
echo "   (but change form action in cart.php first)"
echo ""

echo "========================================="
echo "✅ Ready to test!"
