@extends('layouts.main')

@section('title', 'Hasanuddin University - Event')

@section('content')
    <section class="flex flex-col mx-[70px] my-[70px]">
        <div class="w-full max-md:max-w-full">
            <div class="flex gap-5 max-md:flex-col">
                <div class="flex flex-col items-center w-full max-w-[700px] mx-auto px-4">
                    @foreach ($events_page as $event)
                        <div
                            class="w-full max-w-[650px] mb-8 bg-white rounded-3xl border-indigo-900 border-t-[8px] shadow-[0px_2px_20px_rgba(0,0,0,0.25)]">
                            @if ($event->Event_Image)
                                <div
                                    class="relative w-full h-auto bg-white rounded-3xl shadow-[0px_2px_20px_rgba(0,0,0,0.25)]">
                                    <img src="{{ asset('storage/' . $event->Event_Image) }}"
                                        alt="Featured Image for {{ $event->Event_Title }}"
                                        class="w-full h-[250px] object-cover rounded-t-2xl">
                                </div>
                            @endif
                            <div class="p-6">
                                <h2 class="text-lg md:text-xl font-semibold text-black mb-3 ">
                                    {{ Str::limit($event->Event_Title, 150) }}
                                </h2>
                                <p class="text-gray-700 text-sm md:text-base leading-relaxed line-clamp-3">
                                    {{ Str::limit(html_entity_decode(strip_tags($event->Event_Content)), 150, '...') }}
                                </p>
                                <div class="flex gap-3.5 mt-4 text-xs text-stone-900 items-center">
                                    <img loading="lazy"
                                        src="https://cdn.builder.io/api/v1/image/assets/TEMP/54dd47234fe65d04e71c811fa488ce1f689e2dcd29f8ab5867c046e648130cf9?placeholderIfAbsent=true&apiKey=7c9559411ddd4cc5a44b09e523cbfed7"
                                        alt="" class="object-contain shrink-0 self-start w-6 aspect-[1.2]" />
                                    <time datetime="{{ $event->event_date }}" class="text-xs text-gray-500">
                                        {{ \Carbon\Carbon::parse($event->event_date)->format('d M, Y') }}
                                    </time>
                                    <a href="{{ route('event.show', $event->ID_Event) }}"
                                        class="ml-auto text-sm text-blueThird font-medium hover:underline">
                                        Read More →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                    <div class="mt-6 ">
                        {{ $events_page->appends(request()->query())->links('vendor.pagination.custom') }}
                    </div>
                </div>
                <div class="flex flex-col w-[59%] max-md:ml-0 max-md:w-full">
                    <h2
                        class="px-6 py-2 mt-5 mb-5 text-lg font-semibold text-white bg-indigo-950
                    rounded-md text-center w-full max-w-[650px] mx-auto">
                        Upcoming Event
                    </h2>
                    <div class="flex flex-col items-center w-full max-w-[650px] mx-auto px-4">
                        @foreach ($upcoming_events_page as $upcoming_event)
                            <div
                                class="w-full max-w-[650px] mb-8 bg-white rounded-3xl border-indigo-900 border-t-[6px]
                            shadow-[0px_2px_20px_rgba(0,0,0,0.25)]">
                                <div class="p-6">
                                    <h2 class="text-lg md:text-xl font-semibold text-black mb-3 ">
                                        {{ Str::limit($upcoming_event->Event_Title, 150) }}
                                    </h2>
                                    <p class="text-gray-700 text-sm md:text-base leading-relaxed line-clamp-3">
                                        {{ Str::limit(html_entity_decode(strip_tags($upcoming_event->Event_Content)), 150, '...') }}
                                    </p>
                                    <div class="flex gap-3.5 mt-4 text-xs text-stone-900 items-center">
                                        <img loading="lazy"
                                            src="https://cdn.builder.io/api/v1/image/assets/TEMP/54dd47234fe65d04e71c811fa488ce1f689e2dcd29f8ab5867c046e648130cf9?placeholderIfAbsent=true&apiKey=7c9559411ddd4cc5a44b09e523cbfed7"
                                            alt="" class="object-contain shrink-0 self-start w-6 aspect-[1.2]" />
                                        <time datetime="{{ $upcoming_event->event_date }}" class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($upcoming_event->event_date)->format('d M, Y') }}
                                        </time>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
