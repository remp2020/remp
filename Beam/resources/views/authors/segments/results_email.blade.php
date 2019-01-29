<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authors Segments Results</title>
</head>

<body>

<h2>Configuration:</h2>
<p>
    The number of past days from which results are counted: {{$history_days}} <br/>
    Minimal ratio of author articles to all articles (0 az 1.0): {{$minimal_ratio}} <br/>
    Minimal number of views or author articles: {{$minimal_views}} <br/>
    Minimal average time spent on author articles: {{$minimal_average_timespent}} s<br/>
</p>

<h2>Results</h2>
<br/>
<br/>
<table>
    <tr>
        <th>Author Segment</th>
        <th>Browsers Count</th>
        <th>Users Count</th>
    </tr>
    @foreach ($results as $row)
        <tr>
            <td>{{$row->name}}</td>
            <td>{{$row->browser_count}}</td>
            <td>{{$row->user_count}}</td>
        </tr>
    @endforeach
</table>

</body>
</html>
