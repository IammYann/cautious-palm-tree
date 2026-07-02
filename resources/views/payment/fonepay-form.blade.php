<!DOCTYPE html>
<html>
<head>
    <title>Processing Payment</title>
</head>
<body style="margin: 0; padding: 0; background: #f5f5f5;">
    <form id="fonepay-form" action="{{ $paymentUrl }}" method="GET" style="display: none;">
        @foreach($paymentData as $key => $value)
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endforeach
    </form>
    
    <script>
        document.getElementById('fonepay-form').submit();
    </script>
</body>
</html>
