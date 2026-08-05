<x-mail::message>
# Your Order Has Been Delivered!

Hi,

This is a confirmation that your order **#{{ $orderId }}** for the product **{{ $productName }}** has been successfully delivered.

Thank you for shopping with us!

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
