@extends('campaign::layouts.app')

@section('title', 'Edit snippet')

@section('content')

    <div class="c-header">
        <h2>Snippets</h2>
    </div>
    <div class="card">
        <div class="card-header">
            <h2>Edit snippet</h2>
        </div>
        <div class="card-body card-padding">
            @include('flash::message')

            {{ html()->modelForm($snippet, 'PATCH')->route('snippets.update', ['snippet' => $snippet])->open() }}
                @include('campaign::snippets._form')
            {{ html()->closeModelForm() }}
        </div>
    </div>

    @if (count($usedInBanners) || count($usedInSnippets))
    <div class="card">
        <div class="card-header">
            <h2>Snippet usage</h2>
        </div>
        <div class="card-body card-padding">
            @if (count($usedInBanners))
            <a role="button" class="snippet-usage-toggle" data-toggle="collapse" href="#usage-banners" aria-expanded="false" aria-controls="usage-banners">
                <i class="zmdi zmdi-chevron-right snippet-usage-chevron"></i>
                Used in banners ({{ count($usedInBanners) }})
            </a>
            <div id="usage-banners" class="collapse">
                <ul class="list-group">
                    @foreach($usedInBanners as $banner)
                        <li class="list-group-item">
                            <a href="{{ route('banners.edit', $banner) }}">{{ $banner->name }}</a>
                            @if ($banner->usageDirect)
                                <span class="text-muted">(direct)</span>
                            @elseif ($banner->usageVia)
                                <span class="text-muted">(via <a href="{{ route('snippets.edit', $banner->usageVia) }}">{{ $banner->usageVia->name }}</a>)</span>
                            @endif
                            @if ($banner->campaigns->isNotEmpty())
                                <br><small class="text-muted">{{ $banner->campaigns->pluck('name')->join(', ') }}</small>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if (count($usedInSnippets))
            <a role="button" class="snippet-usage-toggle" data-toggle="collapse" href="#usage-snippets" aria-expanded="false" aria-controls="usage-snippets">
                <i class="zmdi zmdi-chevron-right snippet-usage-chevron"></i>
                Used in snippets ({{ count($usedInSnippets) }})
            </a>
            <div id="usage-snippets" class="collapse">
                <ul class="list-group">
                    @foreach($usedInSnippets as $usedInSnippet)
                        <li class="list-group-item">
                            <a href="{{ route('snippets.edit', $usedInSnippet) }}">{{ $usedInSnippet->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
    </div>
    @endif

@endsection

@push('head')
<style>
    .snippet-usage-toggle {
        cursor: pointer;
        display: block;
        color: inherit;
    }
    .snippet-usage-toggle:hover,
    .snippet-usage-toggle:focus {
        text-decoration: none;
        color: inherit;
    }
    .snippet-usage-chevron {
        display: inline-block;
        transition: transform 0.2s ease;
    }
    .snippet-usage-toggle[aria-expanded="true"] .snippet-usage-chevron {
        transform: rotate(90deg);
    }
    .list-group {
        margin-top: 10px;
    }
    .collapse + .snippet-usage-toggle {
        margin-top: 20px;
    }
</style>
@endpush
