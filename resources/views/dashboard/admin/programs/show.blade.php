<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Program Details') }}
        </h2>
    </x-slot>

    @if (session()->has('success'))
        <div class="alert alert-success bg-green-200 text-green-800 p-4 rounded-lg mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-error bg-red-200 text-red-800 p-4 rounded-lg mb-4">
            {{ session('error') }}
        </div>
    @endif

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-10 space-y-6">
                <!-- Gambar Program -->
                <div class="flex justify-center">
                    @if ($program->program_Image)
                        <img src="{{ asset('storage/' . $program->program_Image) }}" alt="{{ $program->program_Name }}"
                             class="rounded-lg object-cover shadow-md w-full max-h-96">
                    @else
                        <div class="w-full h-40 bg-gray-300 rounded-lg flex items-center justify-center text-gray-500">
                            No Image Available
                        </div>
                    @endif
                </div>

                <!-- Detail Program -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Program Name:</p>
                        <h3 class="text-lg font-semibold text-gray-800">{{ $program->program_Name }}</h3>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Execution Date:</p>
                        <p class="text-gray-800">{{ \Carbon\Carbon::parse($program->Execution_Date)->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Country of Execution:</p>
                        <p class="text-gray-800">{{ $program->Country_of_Execution }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">End Date:</p>
                        <p class="text-gray-800">{{ \Carbon\Carbon::parse($program->End_Date)->format('d M Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Course Credits:</p>
                        <p class="text-gray-800">{{ $program->Course_Credits }} SKS</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Participants Count:</p>
                        <p class="text-gray-800">{{ $program->Participants_Count }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">IE Program:</p>
                        <p class="text-gray-800">{{ $program->ieProgram->ie_program_name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Study Program:</p>
                        <p class="text-gray-800">
                            @if ($program->studyProgram->isNotEmpty())
                                @foreach ($program->studyProgram as $studyProgram)
                                    <span>{{ $studyProgram->study_program_Name }}</span>
                                    @if (!$loop->last), @endif
                                @endforeach
                            @else
                                No study program associated.
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Program Description:</p>
                        <p class="text-gray-800">{{ Str::limit(html_entity_decode(strip_tags($program->program_description)), 150, '...') }}</p>
                    </div>
                </div>

                <div class="mt-10">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-gray-800">Accepted Students</h3>
                        <label for="add-student-modal"
                            class="cursor-pointer text-white text-sm px-4 py-2 bg-blueThird hover:bg-blue-700 rounded-lg">
                            Add Student
                        </label>
                    </div>

                    <!-- Modal untuk menambahkan mahasiswa -->
                    <input type="checkbox" id="add-student-modal" class="modal-toggle" />
                    <div class="modal">
                        <div class="modal-box">
                            <h3 class="font-bold text-lg">Add Student to Program</h3>
                            <form action="{{ route('admin.program.addStudent', $program->ID_program) }}" method="POST">
                                @csrf
                                <div class="form-control">
                                    <label class="label">
                                        <span class="label-text">Student ID (NIM)</span>
                                    </label>
                                    <input type="text" name="nim" placeholder="Enter Student ID (NIM)"
                                        class="input input-bordered w-full" required>
                                </div>
                                <div class="modal-action">
                                    <label for="add-student-modal" class="btn">Cancel</label>
                                    <button type="submit" class="btn btn-success text-white">Add</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <table class="min-w-full table-auto border">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 border">Student Name</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 border">Student ID</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 border">Status</th>
                                <th class="px-6 py-3 text-left text-sm font-medium text-gray-500 border">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if ($acceptedStudents->isEmpty())
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No students have been accepted yet.
                                    </td>
                                </tr>
                            @else
                                @foreach ($acceptedStudents as $student)
                                    <tr class="border-b">
                                        @php
                                            $programsWithStatus = $student->programs;
                                            $program = $programsWithStatus->where('ID_program', $program->ID_program)->first();
                                            $isFinished = $program ? $program->pivot->isFinished : false;
                                            $buttonAction = $isFinished ? 'unfinish' : 'finish';
                                            $buttonText = $isFinished ? 'Cancel' : 'Complete';
                                            $buttonClass = $isFinished ? 'text-red-500' : 'text-green-500';
                                        @endphp

                                        <td class="px-6 py-4 text-sm text-gray-900">{{ $student->Student_Name }}</td>
                                        <td class="px-6 py-4 text-sm text-gray-500">{{ $student->Student_ID_Number }}</td>

                                        <td class="px-6 py-4 text-sm">
                                            @if ($program)
                                                @php
                                                    $isFinished = $program->pivot->isFinished;
                                                    $programStatusClass = $isFinished ? 'text-green-500' : 'text-red-500';
                                                @endphp
                                                <span class="{{ $programStatusClass }}">
                                                    {{ $isFinished ? 'Completed' : 'In Progress' }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="px-6 py-4 text-sm">
                                            @if ($program)
                                                <form action="{{ route('admin.program.updateStatus', ['programId' => $program->ID_program, 'studentId' => $student->ID_Student]) }}"
                                                    method="POST" class="inline-block">
                                                    @csrf
                                                    <input type="hidden" name="action" value="{{ $buttonAction }}">
                                                    <button type="submit" class="{{ $buttonClass }}">
                                                        {{ $buttonText }}
                                                    </button>
                                                </form>

                                                <label for="delete-modal-{{ $student->ID_Student }}" class="text-red-500 cursor-pointer ml-3">Delete</label>
                                                <input type="checkbox" id="delete-modal-{{ $student->ID_Student }}" class="modal-toggle" />
                                                <div class="modal">
                                                    <div class="modal-box">
                                                        <h3 class="font-bold text-lg">Confirm Deletion</h3>
                                                        <p class="py-4">Are you sure you want to delete this student from the program? This action cannot be undone.</p>
                                                        <div class="modal-action">
                                                            <label for="delete-modal-{{ $student->ID_Student }}" class="btn">Cancel</label>
                                                            <form action="{{ route('admin.program.updateStatus', ['programId' => $program->ID_program, 'studentId' => $student->ID_Student]) }}"
                                                                method="POST">
                                                                @csrf
                                                                <input type="hidden" name="action" value="delete">
                                                                <button type="submit" class="btn btn-error text-white text-sm">Delete</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>

                    @if ($acceptedStudents->isNotEmpty())
                        <div class="mt-4">
                            {{ $acceptedStudents->links() }}
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
