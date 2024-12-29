<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Daily Report</title>
</head>
<body>
    <h1>Daily Report for {{ $reportData['date'] }}</h1>
    <p>Total Orders: {{ $reportData['total_orders'] }}</p>
    <p>Total Sales: ${{ $reportData['total_sales'] }}</p>
</body>
</html>
