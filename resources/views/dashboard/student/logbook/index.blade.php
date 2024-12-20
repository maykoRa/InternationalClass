<x-app-layout>
    <x-slot name="header">
        @include('dashboard.partials.header')
    </x-slot>

    <div class="container mx-auto p-4">
        <div class="max-w-7xl mx-6">
            <div class="flex justify-end py-4 gap-4">
                <!-- Upload Certificate Button -->
                <label for="upload-certificate-modal" class="btn btn-info text-white">
                    Upload Certificate
                </label>
                <!-- Add New Button -->
                <a href="{{ route('student.logbook.create', $program->ID_program) }}"
                    class="btn btn-info text-white">
                    Add New
                </a>
            </div>
        </div>
        <!-- Modal for Upload Certificate -->
        <input type="checkbox" id="upload-certificate-modal" class="modal-toggle" />
        <div class="modal">
            <div class="modal-box">
                <h3 class="font-bold text-lg">Upload Certificate</h3>
                <form action="{{ route('student.certificate.store', $program->ID_program) }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="form-control">
                        <label class="label">
                            <span class="label-text">Certificate File (PDF, Max 5MB)</span>
                        </label>
                        <input type="file" name="certificate" accept=".pdf"
                            class="file-input file-input-bordered w-full" required>
                    </div>
                    <div class="modal-action">
                        <label for="upload-certificate-modal" class="btn">Cancel</label>
                        <button type="submit" class="btn btn-success text-white">Upload</button>
                    </div>
                </form>
            </div>
        </div>


        <div class="card bg-white shadow-md mx-6">
            <div class="card-body">
                @if ($logbooks->isNotEmpty())
                    <h2 class="card-title mb-4">Logbook Entry</h2>
                    <div class="overflow-x-auto">
                        <table class="table w-full">
                            <thead>
                                <tr>
                                    <th class="bg-blueThird text-white">No</th>
                                    <th class="bg-blueThird text-white">Image</th>
                                    <th class="bg-blueThird text-white">Activity</th>
                                    <th class="bg-blueThird text-white">Description</th>
                                    <th class="bg-blueThird text-white">Start Time</th>
                                    <th class="bg-blueThird text-white">End Time</th>
                                    <th class="bg-blueThird text-white">Duration (Minutes)</th>
                                    <th class="bg-blueThird text-white">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $totalDuration = 0;
                                @endphp
                                @foreach ($logbooks as $index => $logbook)
                                    @php
                                        $duration = \Carbon\Carbon::parse($logbook->Start_Time)->diffInMinutes(
                                            \Carbon\Carbon::parse($logbook->End_Time),
                                        );
                                        $totalDuration += $duration;
                                    @endphp
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            @if ($logbook->Logbook_Image)
                                                <img src="{{ asset('storage/' . $logbook->Logbook_Image) }}"
                                                    alt="Logbook Image" class="w-28 h-20 rounded-md mx-auto">
                                            @else
                                                No Image
                                            @endif
                                        </td>
                                        <td>{{ $logbook->Logbook_Name }}</td>
                                        <td class="max-w-xs break-words">{{ $logbook->Logbook_Description }}</td>
                                        <td>{{ \Carbon\Carbon::parse($logbook->Start_Time)->format('d M Y H:i') }}</td>
                                        <td>{{ \Carbon\Carbon::parse($logbook->End_Time)->format('d M Y H:i') }}</td>
                                        <td>{{ $duration }}</td>
                                        <td>
                                            <div class="flex justify-center space-x-2">
                                                <a href="{{ route('student.logbook.edit', [$program->ID_program, $logbook->ID_Logbook]) }}"
                                                    class="btn btn-sm btn-outline btn-info">
                                                    Edit
                                                </a>
                                                <label for="delete-modal-{{ $logbook->ID_Logbook }}"
                                                    class="btn btn-sm btn-outline btn-error">
                                                    Delete
                                                </label>
                                            </div>

                                            <input type="checkbox" id="delete-modal-{{ $logbook->ID_Logbook }}"
                                                class="modal-toggle" />
                                            <div class="modal">
                                                <div class="modal-box">
                                                    <h3 class="font-bold text-lg">Confirm Deletion</h3>
                                                    <p class="py-4">Are you sure you want to delete this logbook
                                                        entry?</p>
                                                    <div class="modal-action">
                                                        <label for="delete-modal-{{ $logbook->ID_Logbook }}"
                                                            class="btn btn-outline">Cancel</label>
                                                        <form
                                                            action="{{ route('student.logbook.destroy', [$program->ID_program, $logbook->ID_Logbook]) }}"
                                                            method="POST">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-error">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="6">Total Activity Time (Minutes):</th>
                                    <th>{{ $totalDuration }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <p class="text-center text-lg font-bold">No Logbook Entries Found</p>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
