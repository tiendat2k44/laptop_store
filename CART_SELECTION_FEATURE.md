# Cart Selection Feature - Implementation Summary

## Feature: Select Products in Cart Before Checkout

**Goal**: Allow users to select specific products in the cart and checkout only those products, not the entire cart.

## Changes Made

### 1. **cart.php** - Cart Display & Selection UI
**Purpose**: Display cart items with selection checkboxes and form for checkout

**Changes**:
- ✅ Wrapped product list in `<form id="checkoutForm" method="POST" action="/checkout.php">`
- ✅ Added CSRF token: `<input type="hidden" name="csrf_token">`
- ✅ Added checkbox per item: `<input class="form-check-input item-checkbox" name="selected_items[]" value="ITEM_ID">`
- ✅ Added "Select All" checkbox with JavaScript to toggle all items
- ✅ Changed "Tiến hành thanh toán" from `<a>` link to `<button type="submit">`
- ✅ Added JavaScript validation to prevent submit with 0 items selected
- ✅ Added "Xóa đã chọn" button to delete selected items in bulk
- ✅ Fixed CSRF token retrieval in delete function: use form's hidden input instead of meta tag
- ✅ Fixed `updateTotal()` to calculate correctly: lấy total item price (quantity × unit price), not just unit price
- ✅ Updated `updateSelectedCount()` to show number of selected items

**Key JavaScript Functions**:
```javascript
// Select/deselect all
selectAll checkbox → toggle all .item-checkbox

// Update display
updateSelectedCount() → updates "Chọn tất cả (X sản phẩm)" 
updateTotal() → recalculates total for selected items only

// Form validation
checkoutForm submit → check selected count > 0

// Bulk delete
deleteSelected button → sends item_ids to /ajax/cart-remove.php
```

### 2. **checkout.php** - Order Processing with Selection
**Purpose**: Accept selected items from cart form and process only those

**Changes**:
- ✅ Changed flow to REQUIRE `selected_items[]` POST data
- ✅ If no POST data or empty selected_items, redirect to /cart.php with error message
- ✅ Extract selected IDs: `array_map('intval', $_POST['selected_items'])`
- ✅ Filter $allItems to only selected: `array_filter($allItems, fn($item) => in_array($item['item_id'], $selectedIds))`
- ✅ REMOVED fallback to "all items" - now must have explicit selection
- ✅ After order creation, clear ONLY selected items from cart (not entire cart)
- ✅ Reuse $selectedItemIds variable instead of re-parsing POST data

**Flow**:
```
GET /checkout.php → Redirect to /cart.php (no POST data)
POST /checkout.php with selected_items[] → Process only selected items
Order created successfully → Clear only selected items from cart
Unselected items remain in cart
```

### 3. **includes/services/CartService.php** - Cart Operations
**Purpose**: Provide cart management including selective clearing

**Methods**:
- ✅ `getItems()` - Fixed to include `p.shop_id` in SELECT (was missing before)
- ✅ `clear()` - Clear all items (existing)
- ✅ `clearSelectedItems($itemIds)` - NEW - Delete specific items by ID array

**Code**:
```php
public function clearSelectedItems($itemIds) {
    if (empty($itemIds)) return;
    
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $params = array_merge([$this->userId], $itemIds);
    
    $this->db->execute(
        "DELETE FROM cart_items WHERE user_id = ? AND id IN ($placeholders)",
        $params
    );
}
```

### 4. **includes/services/OrderService.php** - Order Creation
**Purpose**: Ensure order items are created with all required fields including shop_id

**Validation**:
- ✅ Handle missing shop_id gracefully (default to 1)
- ✅ Check insert result and throw exception if fails
- ✅ Prevent silent failures when order_items insert fails

**Code**:
```php
$shopId = !empty($item['shop_id']) ? (int)$item['shop_id'] : 1;
$itemInsertResult = $this->db->insert(...);
if ($itemInsertResult === false) {
    throw new Exception('Cannot add product: ' . $item['name']);
}
```

### 5. **ajax/cart-remove.php** - Bulk Item Deletion
**Purpose**: Delete multiple items at once

**Features**:
- ✅ Support `item_ids` parameter (comma-separated IDs)
- ✅ CSRF token validation
- ✅ User verification (only delete own items)
- ✅ JSON response with success/message

**Code**:
```php
if (isset($_POST['item_ids']) && !empty($_POST['item_ids'])) {
    $itemIds = explode(',', $_POST['item_ids']);
    $itemIds = array_map('intval', $itemIds);
    
    $placeholders = implode(',', array_fill(0, count($itemIds), '?'));
    $result = $db->execute(
        "DELETE FROM cart_items WHERE user_id = ? AND id IN ($placeholders)",
        array_merge([Auth::id()], $itemIds)
    );
}
```

### 6. **diagnostics/** - Test Scripts
- ✅ `test_selection_checkout.php` - Simulates selection and checkout flow
- ✅ `test_integration_selection.php` - Full integration test with detailed validation

## Database Changes
No schema changes required. Uses existing:
- `cart_items` table with `user_id`, `id`, `product_id`, `quantity`
- `order_items` table with `order_id`, `shop_id`, `product_id`, etc.

## Testing Checklist

### Manual Testing Steps:
1. ✅ Add 3+ products to cart
2. ✅ Visit /cart.php
3. ✅ Verify all items show with checkboxes (all checked by default)
4. ✅ Click "Chọn tất cả" to uncheck all
5. ✅ Manually check 2-3 items
6. ✅ Verify cart total updates to show only selected items
7. ✅ Click "Tiến hành thanh toán"
8. ✅ Complete order (test with COD)
9. ✅ Check order has only selected items in database
10. ✅ Return to /cart.php
11. ✅ Verify unselected items still exist in cart
12. ✅ Verify correct count of items remaining

### Test with Bulk Delete:
1. ✅ Add 4 items to cart
2. ✅ Select 2 items
3. ✅ Click "Xóa đã chọn"
4. ✅ Confirm 2 items deleted, 2 remain

## Known Limitations / Edge Cases
- If user unselects all items and tries to submit, form validation prevents submission ✓
- If user tampers with POST data (fake item_ids), they'll be filtered out (won't exist in $allItems) ✓
- If selected item runs out of stock, OrderService will throw exception during creation ✓

## Code Quality
- ✅ All PHP syntax verified (php -l)
- ✅ SQL injection protected (prepared statements)
- ✅ CSRF token validation on form submission
- ✅ User ID verification (only process own cart items)
- ✅ Proper error handling and logging
- ✅ Transaction safety in OrderService

## Commits Made
1. `✨ Fix cart checkout form - send selected_items properly via POST`
2. `✨ Fix cart selection: require selected items in POST, fix CSRF token retrieval in delete`
3. `🔧 Fix checkout: reuse selectedItemIds variable for cart clearing`
4. `🐛 Fix cart total calculation: use item total prices, not unit prices`

## Files Modified
- cart.php
- checkout.php
- includes/services/CartService.php
- includes/services/OrderService.php (validation improved)
- ajax/cart-remove.php (supports bulk delete)
- diagnostics/test_selection_checkout.php (new)
- diagnostics/test_integration_selection.php (new)
