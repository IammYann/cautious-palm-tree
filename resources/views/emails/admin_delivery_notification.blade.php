<x-mail::message>
# An Order Has Been Delivered

This is a notification that order **#{{ $orderId }}** has been successfully delivered.

**Product:** {{ $productName }}
**Buyer:** {{ $buyerName }}

This order is now complete.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
