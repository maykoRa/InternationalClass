<?php

namespace App\Http\Controllers;

use App\Models\IeProgram;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudyProgram;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $data = [
            'title' => 'Manage Program',
        ];

        // Inisialisasi query program
        $programs = Program::query();

        // Menangani pencarian
        $search = $request->input('search');
        if ($search) {
            // Tambahkan filter pencarian berdasarkan nama atau deskripsi program
            $programs = $programs->where('program_Name', 'like', '%' . $search . '%');
        }

        // Role admin dan staff
        if ($user->hasRole('admin') || $user->hasRole('staff')) {
            if ($user->hasRole('staff')) {
                // Ambil ID study program dari staff yang sedang login
                $studyProgramId = $user->staff->ID_study_program;

                // Filter program yang sesuai dengan study program staff
                $programs = $programs->whereHas('studyProgram', function ($query) use ($studyProgramId) {
                    $query->where('ID_study_program', $studyProgramId);
                });
            }

            // Fetch enrollments untuk admin/staff
            $enrollments = Program::with('students')->get();

            // Paginate data program
            $programs = $programs->paginate(5);

            // Tampilkan view untuk admin/staff
            return view('dashboard.admin.programs.index', compact('programs', 'enrollments', 'data'));
        }

        // Role student
        if (!$user->student) {
            abort(403, 'This action is unauthorized.');
        }

        $studyProgramId = $user->student->ID_study_program;
        $ieProgramId = $request->input('ie_program_id');

        // Filter program untuk student
        $programs = $programs->with('ieProgram')
                            ->when($ieProgramId, function ($query) use ($ieProgramId) {
                                return $query->where('ID_Ie_program', $ieProgramId);
                            })
                            ->whereHas('studyProgram', function ($query) use ($studyProgramId) {
                                $query->where('ID_study_program', $studyProgramId);
                            })
                            ->paginate(10);

        $iePrograms = IeProgram::all();

        // Tampilkan view untuk student
        return view('dashboard.student.programs.index', compact('programs', 'iePrograms', 'user'));
    }






    public function enroll(Request $request, $programId)
    {
        // Ambil program yang diinginkan
        $program = Program::findOrFail($programId);
        $student = auth()->user()->student; // Mendapatkan data student yang sedang login

        $existingEnrollment = $student->programs()->wherePivot('status', 'approved')->wherePivot('isFinished', 0)->first();

        if ($existingEnrollment) {
            return redirect()->route('student.program.index')->with('error', 'You cannot enroll in a new program while your current program is still in progress.');
        }

        $existingEnrollmentForProgram = $student->programs()->where('program_enrollment.ID_program', $programId)->first();
        if ($existingEnrollmentForProgram) {
            $currentStatus = $existingEnrollmentForProgram->pivot->status;

            if ($currentStatus === 'pending') {
                return redirect()->route('student.program.index')->with('pending', 'Your enrollment is still pending approval. Please wait for confirmation.');
            }

            if ($currentStatus === 'approved') {
                return redirect()->route('student.program.index')->with('error', 'You are already enrolled in this program.');
            }
        }

        // Cek jumlah peserta yang sudah terdaftar di program
        $currentParticipants = $program->students()->wherePivot('status', 'approved')->count();

        // Cek jika sudah mencapai batas peserta
        if ($currentParticipants >= $program->Participants_Count) {
            return redirect()->route('student.program.index')->with('error', 'This program has reached its participant limit.');
        }

        // Daftarkan student ke program dengan status pending
        $student->programs()->attach($programId, ['status' => 'pending']);

        return redirect()->route('student.program.index')->with('success', 'Your enrollment is pending approval.');
    }




    // Untuk admin/staff memperbarui status pendaftaran
    public function updateStatus(Request $request, $programId, $studentId)
    {
        $action = $request->input('action');
        $status = $request->input('status');

        $student = Student::findOrFail($studentId);

        $program = $student->programs()->wherePivot('ID_program', $programId)->first();

        if ($program) {
            if (in_array($action, ['finish', 'unfinish'])) {
                if ($action === 'finish') {
                    $student->programs()->updateExistingPivot($programId, ['isFinished' => 1]);
                } elseif ($action === 'unfinish') {
                    $student->programs()->updateExistingPivot($programId, ['isFinished' => 0]);
                }
            }

            if ($action === 'delete') {
                $student->programs()->detach($programId);
            }

            if (in_array($status, ['approved', 'rejected'])) {
                if ($status === 'approved') {
                    $student->programs()->updateExistingPivot($programId, ['status' => 'approved']);

                    $student->programs()->wherePivot('ID_program', '!=', $programId)->wherePivot('status', '!=', 'approved')->detach();
                } elseif ($status === 'rejected') {
                    $student->programs()->detach($programId);
                 }

                $approvedCount = $student->programs()->wherePivot('status', 'approved')->count();
                if ($approvedCount >= $program->Participants_Count) {
                    $student->programs()->wherePivot('status', 'pending')->detach();
                }
            }


            return back()->with('success', 'Status updated successfully.');
        }

        return back()->with('error', 'Program not found.');
    }






    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $iePrograms = IeProgram::all();
        $studyPrograms = StudyProgram::all();
        return view('dashboard.admin.programs.create', compact('iePrograms', 'studyPrograms'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user()->id;

        if (auth()->user()->hasRole('admin')) {

            // Validasi input untuk admin, termasuk multiple study program
            $validated = $request->validate([
                'program_Name' => 'required|string|max:255',
                'program_description' => 'required|string',
                'Country_of_Execution' => 'required|string|max:255',
                'Execution_Date' => 'required|date|after_or_equal:' . Carbon::now()->toDateString(),
                'End_Date' => 'required|date|after_or_equal:Execution_Date',
                'Participants_Count' => 'required|integer|min:1',
                'Course_Credits' => 'required|integer|min:1',
                'program_Image' => 'required|image|max:2048',
                'ID_Ie_program' => 'required|exists:ie_programs,ID_Ie_program',
                'ID_study_program' => 'required|array', // Expecting an array of study programs
                'ID_study_program.*' => 'exists:study_programs,ID_study_program', // Each item should be a valid study program
            ]);

            // Data dari validasi
            $data = $validated;
            $data['user_id'] = $user;

            // Tangani upload gambar jika ada
            if ($request->hasFile('program_Image')) {
                if (!$request->file('program_Image')->isValid()) {
                    return redirect()->back()->withErrors(['program_Image' => 'Uploaded file is invalid.']);
                }
                $data['program_Image'] = $request->file('program_Image')->store('images/program', 'public');
            }

            // Buat program baru
            $program = Program::create($data);

            // Menambahkan relasi many-to-many dengan StudyProgram
            $program->studyProgram()->attach($validated['ID_study_program']);

        } else {

            // Untuk staff, ambil ID_study_program dari staff (otomatis terhubung)
            $studyProgram = auth()->user()->staff->ID_study_program;

            // Validasi input untuk staff
            $validated = $request->validate([
                'program_Name' => 'required|string|max:255',
                'program_description' => 'required|string',
                'Country_of_Execution' => 'required|string|max:255',
                'Execution_Date' => 'required|date|after_or_equal:' . Carbon::now()->toDateString(),
                'End_Date' => 'required|date|after_or_equal:Execution_Date',
                'Participants_Count' => 'required|integer|min:1',
                'Course_Credits' => 'required|integer|min:1',
                'program_Image' => 'required|image|max:2048',
                'ID_Ie_program' => 'required|exists:ie_programs,ID_Ie_program',
            ]);

            // Tambahkan data tambahan di luar validasi
            $data = $validated;
            $data['user_id'] = $user;

            // Tangani upload gambar jika ada
            if ($request->hasFile('program_Image')) {
                if (!$request->file('program_Image')->isValid()) {
                    return redirect()->back()->withErrors(['program_Image' => 'Uploaded file is invalid.']);
                }
                $data['program_Image'] = $request->file('program_Image')->store('images/program', 'public');
            }

            // Buat program baru
            $program = Program::create($data);

            $program->studyProgram()->attach($studyProgram);

        }

        // Redirect dengan pesan sukses
        return redirect()->route('admin.program.index')->with('success', 'Program added successfully.');
    }


    /**
     * Display the specified resource.
     */
    public function show(Program $program)
    {
        $user = auth()->user();

        if ($user->hasRole('admin') || $user->hasRole('staff')) {

            $acceptedStudents = $program->students()
                ->wherePivot('status', 'approved')
                ->paginate(10);

            return view('dashboard.admin.programs.show', compact('program', 'acceptedStudents'));

        } else {

            return view('dashboard.student.programs.show', compact('program'));

        }
    }

    public function addStudentToProgram(Request $request, Program $program)
    {
        $request->validate([
            'nim' => 'required|exists:students,Student_ID_Number',
        ]);

        $student = Student::where('Student_ID_Number', $request->nim)->first();

        if (!$student) {
            return redirect()->route('admin.program.show', $program->ID_program)
                     ->with(['error' => 'Student not found.']);
        }

        $existingEnrollment = $student->programs()->wherePivot('status', 'approved')->wherePivot('isFinished', 1)->first();

        if ($existingEnrollment) {
            return redirect()->route('admin.program.show', $program->ID_program)
                     ->with(['error' => 'Student already enrolled or approved for this program.']);
        }

        $programStudyIds = $program->studyProgram->pluck('ID_study_program')->toArray();

        if (!in_array($student->ID_study_program, $programStudyIds)) {
            return redirect()->route('admin.program.show', $program->ID_program)
                     ->with(['error' => 'Student\'s program study does not match the program.']);
        }

        DB::table('program_enrollment')->insert([
            'ID_program' => $program->ID_program,
            'ID_Student' => $student->ID_Student,
            'status' => 'approved',
            'isFinished' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('admin.program.show', $program->ID_program)
                     ->with('success', 'Student successfully added to the program.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program)
    {
        $program->load('studyProgram');
        $iePrograms = IeProgram::all();
        $studyPrograms = StudyProgram::all();
        return view('dashboard.admin.programs.edit', compact('program', 'iePrograms', 'studyPrograms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Program $program)
    {
        // Validasi data berdasarkan role
        if (auth()->user()->hasRole('admin')) {
            $validated = $request->validate([
                'program_Name' => 'required|string|max:255',
                'program_description' => 'required|string',
                'Country_of_Execution' => 'required|string|max:255',
                'Execution_Date' => 'required|date',
                'End_Date' => 'required|date|after_or_equal:Execution_Date',
                'Participants_Count' => 'required|integer|min:1',
                'Course_Credits' => 'required|integer|min:1',
                'program_Image' => 'nullable|image|max:2048',
                'ID_Ie_program' => 'required|exists:ie_programs,ID_Ie_program',
                'ID_study_program' => 'required|array', // Pastikan berupa array
                'ID_study_program.*' => 'exists:study_programs,ID_study_program', // Validasi setiap ID
            ]);
        } else {
            $validated = $request->validate([
                'program_Name' => 'required|string|max:255',
                'program_description' => 'required|string',
                'Country_of_Execution' => 'required|string|max:255',
                'Execution_Date' => 'required|date',
                'End_Date' => 'required|date|after_or_equal:Execution_Date',
                'Participants_Count' => 'required|integer|min:1',
                'Course_Credits' => 'required|integer|min:1',
                'program_Image' => 'nullable|image|max:2048',
                'ID_Ie_program' => 'required|exists:ie_programs,ID_Ie_program',
            ]);
        }

        // Persiapkan data untuk update
        $data = $validated;

        // Kelola upload gambar jika ada
        if ($request->hasFile('program_Image')) {
            // Hapus gambar lama jika ada
            if ($program->program_Image) {
                Storage::disk('public')->delete($program->program_Image);
            }

            // Simpan gambar baru
            $data['program_Image'] = $request->file('program_Image')->store('images/program', 'public');
        }

        // Update data program utama
        $program->update($data);

        // Jika admin, sinkronisasi relasi studyProgram
        if (auth()->user()->hasRole('admin')) {
            $program->studyProgram()->sync($validated['ID_study_program']); // Sinkronisasi ID program studi
        }

        // Redirect ke halaman indeks dengan pesan sukses
        return redirect()->route('admin.program.index')->with('success', 'Program updated successfully.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        // Hapus gambar jika ada
        if ($program->Program_Image && Storage::exists('public/' . $program->Program_Image)) {
            Storage::delete('public/' . $program->Program_Image);
        }

        // Hapus program dari database
        $program->delete();

        // Redirect dengan pesan sukses
        return redirect()->route('admin.program.index')->with('success', 'Program deleted successfully.');
    }
}
