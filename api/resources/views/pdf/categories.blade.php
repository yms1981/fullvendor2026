<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; }
    </style>
</head>
<body>
<h2>Categories List</h2>
<table>
    <thead>
    <tr>
        <th>Category ID</th>
        <th>Category Name</th>
        <th>Created Date</th>
    </tr>
    </thead>
    <tbody>
    @foreach($categories as $category)
        <tr>
            <td>{{ $category->cat_id }}</td>
            <td>{{ $category->category_name }}</td>
            <td>{{ $category->category_created_at }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
