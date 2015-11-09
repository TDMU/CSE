<?php

namespace App\Model\CreateDocuments;

use App\CacheSpeciality;
use App\ConsultingGrades;
use App\Grades;
use App\GradesFiles;
use App\Model\Contingent\Students;
use Chumper\Zipper\Facades\Zipper;
use Illuminate\Database\Eloquent\Model;
use App\Model\Contingent\Students as ContStudent;
use File;
use Storage;
use App\CacheDepartment;


class ConsultingDocuments extends Model
{
    protected $DOC_PATH; // for puts files

    protected $studentsOfGroup; // Students of group

    protected $dataGradesOfStudentsGroups; // for find all groups

    protected $dataOfFile; // each module

    protected $dataEachOfFile; // select module

    protected $speciality; // get from cache speciality

    protected $department; // get from cache department

    protected $idFileGrade = 0;

    protected $shablon;

    private $numModule = 1;

    public function __construct($idFileGrade)
    {
        $this->dataOfFile = ConsultingGrades::where('id_num_plan',$idFileGrade)->get();

        /**
         * get data from bd about module (generals data for each docs)
         */
        $this->DOC_PATH = DIRECTORY_SEPARATOR.'consultingDocuments'.DIRECTORY_SEPARATOR;
        $this->speciality = CacheSpeciality::getSpeciality(Students::getStudentSpeciality($this->dataOfFile[0]->id_student))->name;
        $this->department = CacheDepartment::getDepartment(Students::getStudentDepartment($this->dataOfFile[0]->id_student))->name;
        $this->dataEachOfFile = GradesFiles::where('ModuleVariantID', $this->dataOfFile[0]->id_num_plan)->get()->first();

        Storage::deleteDirectory($this->DOC_PATH.'docs');
    }

    /**
     * Public func for prepare get data
     */
    public function formDocuments()
    {
        /**
         * find each student and sort of groupNum
         */
        foreach ($this->dataOfFile as $student) {
                $this->studentsOfGroup[Students::getStudentGroup($student['id_student'])][] = $student;
        }
        $this->formHtml();
        Zipper::make(public_path() . $this->DOC_PATH . DIRECTORY_SEPARATOR.'Docs.zip')->add(glob(public_path() . $this->DOC_PATH . DIRECTORY_SEPARATOR.'docs'));
        return $this->DOC_PATH . DIRECTORY_SEPARATOR.'Docs.zip';
    }

    /**
     * Agregate functions for create shablon
     */
    private function formHtml()
    {
        foreach ($this->studentsOfGroup as $group=>$students) {
            $this->createHeaderShablon($group);
            $num = 1;
            foreach($students as $student) {
                $this->shablon .= "<tr><td width=10%>" . ($num++) . "</td><td width=50%>" . Students::getStudentFIO($student->id_student) . "</td><td width=15%>" . ContStudent::getStudentBookNum($student->id_student) . "</td><td width=10%>" . $student->grade_consulting . "</td></tr>";
            }
            $this->createFooterShablon();
            File::makeDirectory(public_path() . $this->DOC_PATH . DIRECTORY_SEPARATOR.'docs', 0775, true, true);
            File::put(public_path() . $this->DOC_PATH . DIRECTORY_SEPARATOR.'docs'.DIRECTORY_SEPARATOR . $this->numModule . '.' . $group . '.doc', $this->shablon);
        }
    }

    /**
     * Convert semester to course
     * @return int
     */
    private function findSemester()
    {
        return ($this->dataEachOfFile->Semester & 1) ? ($this->dataEachOfFile->Semester + 1) / 2 : $this->dataEachOfFile->Semester / 2;
    }

    /**
     * Create block for header of shablon
     */
    private function createHeaderShablon($group)
    {
        $this->shablon = "
        <html>
        <head>
            <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
            <style>
                body {font-size:14px;}
            </style>
        </head>
        <body>
        <p align=center>МІНІСТЕРСТВО ОХОРОНИ ЗДОРОВЯ УКРАЇНИ </p>
        <p align=center><b><u>Тернопільський державний медичний університет імені І.Я. Горбачевського</u></b></p>
        <span align=left> Факультет <u>" . $this->department . "</u></span><br>
        <span align=left> Спеціальність <u>" . $this->speciality . "</u></span>
        <span align=right>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Група_<u>" . $group . "</u>___</span>
        &nbsp;&nbsp;&nbsp;&nbsp;" . $this->dataEachOfFile->EduYear . "/" . ($this->dataEachOfFile->EduYear + 1) . " &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Курс _<u>" . $this->findSemester() . "</u>___<br />
        <p align=center>ВІДОМІСТЬ №____ </p>
        <p>З <u>" . $this->dataEachOfFile->ModuleNum . ". " . $this->dataEachOfFile->NameDiscipline . "</u> - <u>" . $this->dataEachOfFile->NameModule . "</u></p>
        <p>За _<u>" . $this->dataEachOfFile->Semester . "</u>___ навчальний семестр, екзамен <u>_" . date('d.m.Y') . "___</u></p>
        <table class=guestbook width=600 align=center cellspacing=0 cellpadding=3 border=1>
            <tr>
                <td width=10%>
                    <b>№ п/п</b>
                </td>
                <td width=50%>
                    <b>Прізвище, ім'я по-батькові</b>
                </td>
                <td width=10%>
                    <b>№ індиві-дуального навч. плану</b>
                </td>
                <td width=10%>
                    <b>Кількість балів</b>
                </td>
            </tr>
        ";
    }

    /**
     * Create block for footer of shablon
     */
    private function createFooterShablon()
    {
        $this->shablon .= "</table><br />";
    }


}