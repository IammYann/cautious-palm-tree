<?php
// Test eSewa signature generation
$secret = '8gBm/:&EnhH.1/q';

// Test order 65: amount=299.21, uuid=65-260625113508
$msg = 'total_amount=299.21,transaction_uuid=65-260625113508,product_code=EPAYTEST';
$sig = base64_encode(hash_hmac('sha256', $msg, $secret, true));
echo "Signature for order 65: " . $sig . PHP_EOL;

// eSewa sandbox typically returns total_amount WITH comma for thousands
// For 299.21 there's no comma, but what about the decimal format?
// eSewa may return "299.21" or "299.21" - both should match

// Test with amount that could be ambiguous
$msg2 = 'total_amount=250.78,transaction_uuid=64-260625113416,product_code=EPAYTEST';
$sig2 = base64_encode(hash_hmac('sha256', $msg2, $secret, true));
echo "Signature for order 64: " . $sig2 . PHP_EOL;

// Verify our float comparison logic
$esewa_amount = '299.21';
$order_amount = 299.21;
echo "Float compare: " . ((float)$esewa_amount == (float)$order_amount ? 'MATCH' : 'MISMATCH') . PHP_EOL;

// What if eSewa returns with comma?
$esewa_with_comma = '299.21';
$cleaned = str_replace(',', '', $esewa_with_comma);
echo "Cleaned: $cleaned, Compare: " . ((float)$cleaned == (float)$order_amount ? 'MATCH' : 'MISMATCH') . PHP_EOL;
