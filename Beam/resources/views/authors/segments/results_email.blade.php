<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Authors Segments Results</title>
</head>

<body>

<h3>Configuration</h3>
<ul>
    <li>number of past days from which results are counted: <b>{{$history_days}}</b></li>
    <li>minimal ratio of (author articles/all articles): <b>{{$minimal_ratio}}</b></li>
    <li>minimal number of views or author articles: <b>{{$minimal_views}}</b> <br/></li>
    <li>minimal average time spent on author articles: <b>{{$minimal_average_timespent}}</b></li>
</ul>

<h3>Results</h3>
@if($results)
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
@else
    <p>No author segments found for given configuration.</p>
@endif

</body>
</html>
