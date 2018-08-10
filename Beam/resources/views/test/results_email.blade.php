<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Autorske segmenty - Vysledky</title>
</head>

<body>

<h2>Nastavenia</h2>
<p>
Pocet dni dozadu za ktore pocitam segmenty: {{$historyDays}} <br />
Min. pomer precitanych clankov daneho autora ku vsetkym ostatnym (0 az 1.0): {{$minimalRatio}} <br />
Min. pocet pageviews clankov daneho autora: {{$minimalViews}} <br />
Min. priemerny cas straveny na clanku (v sekundach): {{$minimalAverageTimespent}} <br />
</p>

<h2>Vysledky</h2>

<br />
<br />
<table>
    <tr>
        <th>Author Segment</th>
        <th>#browsers</th>
        <th>#users </th>
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
