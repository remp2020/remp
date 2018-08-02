<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Zadaj parametre </title>
</head>

<body>



<h3>Konfiguracia autorskych segmentov</h3>

<pre>
Pri prepocte uzivatelov autorskych segmentov uvazujeme data za obdobie poslednych 30 dni.
Momentalne su do segmentu daneho autora zaradeni uzivatelia ktori (priklad nastaveni):

- podiel clankov daneho autora zo vsetkych ktore uzivatel precital je aspon 25%
- pocet pageviews clankov daneho autora je aspon 5
- priemerny cas straveny na tychto clankoch je aspon 2 min

Nizsie mozem experimentovat s tymito hodnotami:
(0 znamena ze sa podmienka efektivne nepouzije)
</pre>


<form method="post" action="{{ route('test.show-results') }}">
    {{ csrf_field() }}

    <table>
        <tr>
            <td>Pocet dni dozadu za ktore pocitam segmenty:</td>
            <td></td>
        </tr>

        <tr>
            <td>
                {{ Form::radio('history', '30', true) }}
                <label for="days_30">30 dni</label>
            </td>
            <td>
            </td>
        </tr>

        <tr>
            <td>
                {{ Form::radio('history', '60') }}
                <label for="days_60">60 dni</label>
            </td>
            <td>

            </td>
        </tr>

        <tr>
            <td>
                {{ Form::radio('history', '90') }}
                <label for="days_90">90 dni</label>
            </td>
            <td>

            </td>
        </tr>

        <tr>
            <td>
                <label for="min_ratio">Min. pomer precitanych clankov daneho autora ku vsetkym ostatnym (0 az 1.0)</label>
            </td>
            <td>
                <input id="min_ratio" value="{{ $min_ratio ?? '' }}" name="min_ratio" placeholder="0.25" type="text" />
            </td>
        </tr>

        <tr>
            <td>
                <label for="min_views">Min. pocet pageviews clankov daneho autora</label>
            </td>
            <td>
                <input id="min_views" value="{{ $min_views ?? '' }}" placeholder="5" name="min_views" type="text" />
            </td>
        </tr>

        <tr>
            <td>
                <label for="min_average_timespent">Min. priemerny cas straveny na clanku (v sekundach)</label>
            </td>

            <td>
                <input id="min_average_timespent"  value="{{ $min_average_timespent ?? '' }}" placeholder="120" name="min_average_timespent" type="text" />
            </td>
        </tr>

    </table>

    <input type="submit" value="Prepocitaj pocty browserov v segmente" />
</form>

@if(isset($results))
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
@endif

</body>
</html>
