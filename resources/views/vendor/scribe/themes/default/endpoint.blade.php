@php
    use Knuckles\Scribe\Tools\Utils as u;
    /** @var  Knuckles\Camel\Output\OutputEndpointData $endpoint */
@endphp

<h2 id="{!! $endpoint->fullSlug() !!}">{{ $endpoint->name() }}</h2>

<p>
@component('scribe::components.badges.auth', ['authenticated' => $endpoint->isAuthed()])
@endcomponent
@component('scribe::components.badges.deprecated', ['deprecated' => $endpoint->metadata->deprecated])
@endcomponent
</p>

{!! Parsedown::instance()->text($endpoint->metadata->description ?: '') !!}

<span id="example-requests-{!! $endpoint->endpointId() !!}">
<blockquote>{{ u::trans("scribe::endpoint.example_request") }}:</blockquote>

@foreach($metadata['example_languages'] as $language)

<div class="{{ $language }}-example">
    @include("scribe::partials.example-requests.$language")
</div>

@endforeach
</span>

<span id="example-responses-{!! $endpoint->endpointId() !!}">
@if($endpoint->isGet() || $endpoint->hasResponses())
    @foreach($endpoint->responses as $response)
        <blockquote>
            <p>{{ u::trans("scribe::endpoint.example_response") }} ({{ $response->fullDescription() }}):</p>
        </blockquote>
        @if(count($response->headers))
        <details class="annotation">
            <summary style="cursor: pointer;">
                <small onclick="textContent = parentElement.parentElement.open ? 'Show headers' : 'Hide headers'">Show headers</small>
            </summary>
            <pre><code class="language-http">@foreach($response->headers as $header => $value)
{{ $header }}: {{ is_array($value) ? implode('; ', $value) : $value }}
@endforeach </code></pre></details> @endif
        <pre>
@if($response->isBinary())
<code>{!! u::trans("scribe::endpoint.responses.binary") !!} - {{ htmlentities(str_replace("<<binary>>", "", $response->content)) }}</code>
@elseif($response->status == 204)
<code>{!! u::trans("scribe::endpoint.responses.empty") !!}</code>
@else
@php($parsed = json_decode($response->content))
{{-- If response is a JSON string, prettify it. Otherwise, just print it --}}
<code class="language-json" style="max-height: 300px;">{!! htmlentities($parsed != null ? json_encode($parsed, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : $response->content) !!}</code>
@endif </pre>
    @endforeach
@endif
</span>
<section>
    <h3>
        {{ u::trans("scribe::endpoint.request") }}
    </h3>
    @foreach($endpoint->httpMethods as $method)
        <p>
            @component('scribe::components.badges.http-method', ['method' => $method])@endcomponent
            <b><code>{{$endpoint->uri}}</code></b>
        </p>
    @endforeach
    @if(count($endpoint->headers))
        <h4 class="fancy-heading-panel"><b>{{ u::trans("scribe::endpoint.headers") }}</b></h4>
        @foreach($endpoint->headers as $name => $example)
            <?php
                $htmlOptions = [];
            if ($endpoint->isAuthed() && 'header' == $metadata['auth']['location'] && $metadata['auth']['name'] == $name) {
                $htmlOptions = ['class' => 'auth-value'];
            }
            ?>
            <div style="padding-left: 28px; clear: unset;">
                @component('scribe::components.field-details', [
                  'name' => $name,
                  'type' => null,
                  'required' => true,
                  'deprecated' => false,
                  'description' => null,
                  'example' => $example,
                  'endpointId' => $endpoint->endpointId(),
                  'component' => 'header',
                  'isInput' => false,
                  'html' => $htmlOptions,
                ])
                @endcomponent
            </div>
        @endforeach
    @endif
    @if(count($endpoint->urlParameters))
        <h4 class="fancy-heading-panel"><b>{{ u::trans("scribe::endpoint.url_parameters") }}</b></h4>
        @foreach($endpoint->urlParameters as $attribute => $parameter)
            <div style="padding-left: 28px; clear: unset;">
                @component('scribe::components.field-details', [
                  'name' => $parameter->name,
                  'type' => $parameter->type ?? 'string',
                  'required' => $parameter->required,
                  'deprecated' => $parameter->deprecated,
                  'description' => $parameter->description,
                  'example' => $parameter->example ?? '',
                  'enumValues' => $parameter->enumValues,
                  'endpointId' => $endpoint->endpointId(),
                  'component' => 'url',
                  'isInput' => false,
                ])
                @endcomponent
            </div>
        @endforeach
    @endif
    @if(count($endpoint->queryParameters))
        <h4 class="fancy-heading-panel"><b>{{ u::trans("scribe::endpoint.query_parameters") }}</b></h4>
        @foreach($endpoint->queryParameters as $attribute => $parameter)
                <?php
                $htmlOptions = [];
            if ($endpoint->isAuthed() && 'query' == $metadata['auth']['location'] && $metadata['auth']['name'] == $attribute) {
                $htmlOptions = ['class' => 'auth-value'];
            }
            ?>
            <div style="padding-left: 28px; clear: unset;">
                @component('scribe::components.field-details', [
                  'name' => $parameter->name,
                  'type' => $parameter->type,
                  'required' => $parameter->required,
                  'deprecated' => $parameter->deprecated,
                  'description' => $parameter->description,
                  'example' => $parameter->example ?? '',
                  'enumValues' => $parameter->enumValues,
                  'endpointId' => $endpoint->endpointId(),
                  'component' => 'query',
                  'isInput' => false,
                  'html' => $htmlOptions,
                ])
                @endcomponent
            </div>
        @endforeach
    @endif
    @if(count($endpoint->nestedBodyParameters))
        <h4 class="fancy-heading-panel"><b>{{ u::trans("scribe::endpoint.body_parameters") }}</b></h4>
        <x-scribe::nested-fields
                :fields="$endpoint->nestedBodyParameters" :endpointId="$endpoint->endpointId()"
        />
    @endif
</section>

@if(count($endpoint->responseFields))
    <h3>{{ u::trans("scribe::endpoint.response") }}</h3>
    <h4 class="fancy-heading-panel"><b>{{ u::trans("scribe::endpoint.response_fields") }}</b></h4>
    <x-scribe::nested-fields
            :fields="$endpoint->nestedResponseFields" :endpointId="$endpoint->endpointId()"
            :isInput="false"
    />
@endif
