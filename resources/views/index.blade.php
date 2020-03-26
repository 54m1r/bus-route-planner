@extends('layouts.backend')

@section('content')
    <div class="content">
        <form action="{{ route('search') }}" method="post">
            <div class="row push">
                @csrf

                <div class="col-5">
                    <select class="js-select2 form-control" id="select-1" name="start" style="width: 100%;" data-placeholder="Wahlen Sie hier Ihre Abfahrshaltestelle aus...">
                        @if(isset($result))
                            <option value="{{ $start->id }}">{{ $start->name }}</option>
                        @else
                            <option></option>
                        @endif
                        @foreach(\App\Station::all() as $station)
                            @if(isset($result))
                                @if($station != $start)
                                    <option value="{{ $station->id }}">{{ $station->name }}</option>
                                @endif
                            @else
                                <option value="{{ $station->id }}">{{ $station->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-5">
                    <select class="js-select2 form-control" id="select-2" name="destination" style="width: 100%;" data-placeholder="Wahlen Sie hier Ihre Ankunftshaltestelle aus...">
                        @if(isset($result))
                            <option value="{{ $destination->id }}">{{ $destination->name }}</option>
                        @else
                            <option></option>
                        @endif
                        @foreach(\App\Station::all() as $station)
                            @if(isset($result))
                                @if($station != $start)
                                    <option value="{{ $station->id }}">{{ $station->name }}</option>
                                @endif
                            @else
                                <option value="{{ $station->id }}">{{ $station->name }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>
                <div class="col-2">
                    <button style="width: 100%;" type="submit" class="btn btn-sm btn-alt-primary">
                        <i class="fa fa-search"></i> Suchen
                    </button>
                </div>
            </div>
        </form>

        @if(isset($result))
            <div class="block">
                <div class="block-content block-content-full tab-content overflow-hidden">
                    <div class="font-size-h3 py-5 font-w600 text-center">
                        Es wurde <span class="text-primary font-w700">eine</span> Verbindung gefunden.
                    </div>
                    <div class="font-size-h5 mb-20 py-20 font-w600 text-center border-b">
                        Von {{ $start->name }} nach {{ $destination->name }}
                    </div>
                </div>
                <div class="block-content block-content-full">
                    <ul class="list list-timeline list-timeline-modern pull-t">
                        @foreach($path as $item)
                            @if($item['stations'][0] != $destination->name)
                                <li>
                                    <i class="list-timeline-icon bg-{{ \App\Route::where('name', '=', $item['route'])->firstOrFail()->color }}">{{ \App\Route::where('name', '=', $item['route'])->firstOrFail()->name }}</i>
                                    <div class="list-timeline-content">
                                        <p class="font-w600">Von {{ $item['stations'][0] }} nach {{ $item['stations'][sizeof($item['stations'])-1] }}</p>
                                        <div class="row">
                                            <div class="col-sm-6 col-xl-4">
                                                <ol>
                                                    @foreach($item['stations'] as $item)
                                                         <li>{{ $item }}</li>
                                                    @endforeach
                                                </ol>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
    </div>
    @endif
@endsection
