<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sections Segments Results</title>
</head>

<body>

<h3>Configuration</h3>
<ul>
    <li>number of past days from which results are counted: <b>{{$history_days}}</b></li>
    <li>minimal ratio of (section articles/all articles): <b>{{$minimal_ratio}}</b></li>
    <li>minimal number of views or section articles: <b>{{$minimal_views}}</b> <br/></li>
    <li>minimal average time spent on section articles: <b>{{$minimal_average_timespent}}</b></li>
</ul>

<h3>Results</h3>
@if($results)
    <table>
        <tr>
            <th>Section Segment</th>
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
    <p>No section segments found for given configuration.</p>
@endif

</body>
</html>
