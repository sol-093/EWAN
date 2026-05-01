<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/core/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Run this script from the command line.\n";
    exit(1);
}

$curriculum = [
    'Computer Science' => [
        ['1st Year - 1st Semester', 'GEC006', 'Science, Technology and Society', 3, 0, 3, 0, ''],
        ['1st Year - 1st Semester', 'GEE003', 'Living in the IT Era', 3, 0, 3, 0, ''],
        ['1st Year - 1st Semester', 'NSTP001', 'National Service Training Program 1', 3, 0, 3, 0, ''],
        ['1st Year - 1st Semester', 'PE001', 'Physical Activities Toward Health and Fitness 1: Movement Competency Training', 2, 0, 2, 0, ''],
        ['1st Year - 1st Semester', 'COMP101', 'Introduction to Computing', 2, 1, 2, 3, ''],
        ['1st Year - 1st Semester', 'COMP102', 'Computer Programming 1', 2, 1, 2, 3, ''],
        ['1st Year - 1st Semester', 'COSC101', 'Discrete Structures 1', 3, 0, 3, 0, ''],
        ['1st Year - 1st Semester', 'REGAL100', 'On Becoming Regals', 1, 0, 1, 0, ''],

        ['1st Year - 2nd Semester', 'GEC004', 'Purposive Communication', 3, 0, 3, 0, ''],
        ['1st Year - 2nd Semester', 'GEC003', 'Mathematics in the Modern World', 3, 0, 3, 0, ''],
        ['1st Year - 2nd Semester', 'NSTP002', 'National Service Training Program 2', 3, 0, 3, 0, 'NSTP001'],
        ['1st Year - 2nd Semester', 'PE002', 'Physical Activities Toward Health and Fitness 2: Exercise-based Fitness Activities', 2, 0, 2, 0, 'PE001'],
        ['1st Year - 2nd Semester', 'COMP103', 'Computer Programming 2', 2, 1, 2, 3, 'COMP102'],
        ['1st Year - 2nd Semester', 'COAL101', 'Web Systems and Technologies', 2, 1, 2, 3, 'COMP101'],
        ['1st Year - 2nd Semester', 'COSC102', 'Discrete Structures 2', 3, 0, 3, 0, 'COSC101'],

        ['2nd Year - 1st Semester', 'GEC005', 'Readings in Philippine History', 3, 0, 3, 0, ''],
        ['2nd Year - 1st Semester', 'GEC007', 'The Contemporary World', 3, 0, 3, 0, ''],
        ['2nd Year - 1st Semester', 'PE003D', 'Physical Activities Toward Health and Fitness 3: Dance', 2, 0, 2, 0, 'PE001, PE002'],
        ['2nd Year - 1st Semester', 'COSC103', 'Object Oriented Programming', 2, 1, 2, 3, 'COMP102'],
        ['2nd Year - 1st Semester', 'COMP104', 'Data Structures and Algorithm', 2, 1, 2, 3, 'COMP103'],
        ['2nd Year - 1st Semester', 'MATH102', 'Linear Algebra', 3, 0, 3, 0, ''],
        ['2nd Year - 1st Semester', 'COSC104', 'Programming Languages', 2, 1, 2, 3, 'COMP103'],

        ['2nd Year - 2nd Semester', 'GEE008', 'The Entrepreneurial Mind', 3, 0, 3, 0, ''],
        ['2nd Year - 2nd Semester', 'RZL001', 'Life and Works of Rizal', 3, 0, 3, 0, ''],
        ['2nd Year - 2nd Semester', 'PE004M', 'Physical Activities Toward Health and Fitness 4: Martial Arts', 2, 0, 2, 0, 'PE001, PE002'],
        ['2nd Year - 2nd Semester', 'COSC106', 'Operating Systems', 2, 1, 2, 3, ''],
        ['2nd Year - 2nd Semester', 'COMP105', 'Information Management', 2, 1, 2, 3, ''],
        ['2nd Year - 2nd Semester', 'COSC107', 'Computer Architecture and Organization', 2, 1, 2, 3, ''],
        ['2nd Year - 2nd Semester', 'COSC105', 'Algorithm and Complexity', 3, 0, 3, 0, ''],

        ['3rd Year - 1st Semester', 'GEC008', 'Understanding the Self', 3, 0, 3, 0, ''],
        ['3rd Year - 1st Semester', 'COSC108', 'Automata Theory and Formal Languages', 3, 0, 3, 0, 'COMP104, COSC102, COSC104'],
        ['3rd Year - 1st Semester', 'COSC109', 'Human-Computer Interaction', 0, 1, 0, 3, 'COAL101'],
        ['3rd Year - 1st Semester', 'COMP106', 'Application Development and Emerging Technologies', 2, 1, 2, 3, 'COMP101, COMP103, COMP107'],
        ['3rd Year - 1st Semester', 'COSC110', 'Software Engineering 1', 2, 1, 2, 3, ''],
        ['3rd Year - 1st Semester', 'CSEL101-CSEL107', 'Professional Electives 1', 3, 0, 3, 0, ''],
        ['3rd Year - 1st Semester', 'MATH104', 'Calculus', 3, 0, 3, 0, 'MATH102'],
        ['3rd Year - 1st Semester', 'COAL103', 'Methods of Research in Computing', 3, 0, 3, 0, '3rd Year Standing'],

        ['3rd Year - 2nd Semester', 'GEC001', 'Art Appreciation', 3, 0, 3, 0, ''],
        ['3rd Year - 2nd Semester', 'GEC002', 'Ethics', 3, 0, 3, 0, ''],
        ['3rd Year - 2nd Semester', 'COAL102', 'Computational Science', 3, 0, 3, 0, ''],
        ['3rd Year - 2nd Semester', 'COSC115A', 'Computer Science Thesis 1', 3, 0, 3, 0, 'COAL103'],
        ['3rd Year - 2nd Semester', 'COSC111', 'Software Engineering 2', 2, 1, 2, 3, 'COSC110'],
        ['3rd Year - 2nd Semester', 'COAL104', 'Computer Graphics and Visualization', 2, 1, 2, 3, ''],
        ['3rd Year - 2nd Semester', 'CSEL101L-CSEL107L', 'Professional Electives 2', 3, 0, 3, 0, ''],

        ['4th Year - 1st Semester', 'COSC115B', 'Computer Science Thesis 2', 3, 0, 3, 0, 'COSC115A'],
        ['4th Year - 1st Semester', 'COSC112', 'Networks and Communications', 2, 1, 2, 3, 'COSC106'],
        ['4th Year - 1st Semester', 'COSC113', 'Information Assurance and Security', 2, 1, 2, 3, 'COSC106'],
        ['4th Year - 1st Semester', 'CSEL108', 'Professional Electives 3', 3, 0, 3, 0, ''],
        ['4th Year - 1st Semester', 'COAL105', 'Social Issues and Professional Practice', 3, 0, 3, 0, '4th Year Standing'],

        ['4th Year - 2nd Semester', 'COSC116', 'Practicum / Internship', 6, 0, 6, 0, '4th Year Standing'],
        ['4th Year - 2nd Semester', 'CSEL109', 'Professional Electives 4', 3, 0, 3, 0, ''],
        ['4th Year - 2nd Semester', 'COAL106', 'Capstone Deployment and Documentation', 3, 0, 3, 0, 'COSC115B'],
        ['4th Year - 2nd Semester', 'GEE009', 'Technopreneurship', 3, 0, 3, 0, ''],
    ],
];

$terms = [
    '1st Year - 1st Semester',
    '1st Year - 2nd Semester',
    '2nd Year - 1st Semester',
    '2nd Year - 2nd Semester',
    '3rd Year - 1st Semester',
    '3rd Year - 2nd Semester',
    '4th Year - 1st Semester',
    '4th Year - 2nd Semester',
];

function buildCurriculum(string $prefix, array $termSubjects): array
{
    $rows = [];
    foreach ($termSubjects as $termIndex => $subjects) {
        foreach ($subjects as $subjectIndex => $subject) {
            $number = (($termIndex + 1) * 100) + $subjectIndex + 1;
            $code = $prefix . $number;
            $title = $subject[0];
            $creditLec = $subject[1] ?? 3;
            $creditLab = $subject[2] ?? 0;
            $contactLec = $subject[3] ?? $creditLec;
            $contactLab = $subject[4] ?? ($creditLab * 3);
            $prerequisite = $subject[5] ?? '';
            $rows[] = [$GLOBALS['terms'][$termIndex], $code, $title, $creditLec, $creditLab, $contactLec, $contactLab, $prerequisite];
        }
    }

    return $rows;
}

$curriculum['Information System'] = buildCurriculum('ISY', [
    [
        ['Introduction to Information Systems', 3, 0],
        ['Computer Programming 1', 2, 1],
        ['Digital Literacy and Productivity Tools', 2, 1],
        ['Mathematics in the Modern World', 3, 0],
        ['Purposive Communication', 3, 0],
    ],
    [
        ['Computer Programming 2', 2, 1, 2, 3, 'ISY102'],
        ['Data Management Fundamentals', 2, 1],
        ['Business Process Concepts', 3, 0],
        ['Discrete Mathematics for IS', 3, 0],
        ['Readings in Philippine History', 3, 0],
    ],
    [
        ['Database Management Systems 1', 2, 1, 2, 3, 'ISY202'],
        ['Web Systems and Technologies', 2, 1],
        ['Systems Analysis and Design', 3, 0],
        ['Accounting for Information Systems', 3, 0],
        ['The Contemporary World', 3, 0],
    ],
    [
        ['Database Management Systems 2', 2, 1, 2, 3, 'ISY301'],
        ['Information Management', 3, 0],
        ['Enterprise Architecture', 3, 0],
        ['Human Computer Interaction', 2, 1],
        ['Ethics', 3, 0],
    ],
    [
        ['Application Development and Emerging Technologies', 2, 1, 2, 3, 'ISY401'],
        ['Project Management', 3, 0],
        ['Business Analytics', 2, 1],
        ['IT Infrastructure and Network Technologies', 2, 1],
        ['Methods of Research in IS', 3, 0],
    ],
    [
        ['Information Systems Security', 3, 0],
        ['Enterprise Systems', 2, 1],
        ['IS Strategy, Management, and Acquisition', 3, 0],
        ['Capstone Project 1', 3, 0, 3, 0, 'ISY505'],
        ['Professional Elective 1', 3, 0],
    ],
    [
        ['Capstone Project 2', 3, 0, 3, 0, 'ISY604'],
        ['IT Audit and Controls', 3, 0],
        ['Professional Elective 2', 3, 0],
        ['Technopreneurship', 3, 0],
        ['Social and Professional Issues in IT', 3, 0],
    ],
    [
        ['Practicum / Internship', 6, 0, 6, 0, '4th Year Standing'],
        ['Seminar in Information Systems', 3, 0],
        ['Portfolio and Career Preparation', 3, 0],
        ['Professional Elective 3', 3, 0],
    ],
]);

$curriculum['Nursing'] = buildCurriculum('NUR', [
    [
        ['Anatomy and Physiology', 3, 2, 3, 6],
        ['Theoretical Foundations in Nursing', 3, 0],
        ['Health Assessment', 2, 1, 2, 3],
        ['Biochemistry for Nursing', 3, 1, 3, 3],
        ['Understanding the Self', 3, 0],
    ],
    [
        ['Microbiology and Parasitology', 3, 1, 3, 3],
        ['Fundamentals of Nursing Practice', 3, 2, 3, 6, 'NUR101'],
        ['Nutrition and Diet Therapy', 3, 0],
        ['Purposive Communication', 3, 0],
        ['Physical Activities Toward Health and Fitness 1', 2, 0],
    ],
    [
        ['Community Health Nursing 1', 3, 1, 3, 3],
        ['Pharmacology', 3, 0],
        ['Care of Mother, Child, and Adolescent', 4, 2, 4, 6, 'NUR202'],
        ['Pathophysiology', 3, 0],
        ['Readings in Philippine History', 3, 0],
    ],
    [
        ['Medical-Surgical Nursing 1', 4, 2, 4, 6, 'NUR303'],
        ['Psychiatric Nursing', 3, 1, 3, 3],
        ['Nursing Informatics', 2, 1],
        ['Community Health Nursing 2', 3, 1, 3, 3, 'NUR301'],
        ['Ethics', 3, 0],
    ],
    [
        ['Medical-Surgical Nursing 2', 4, 2, 4, 6, 'NUR401'],
        ['Maternal and Child Nursing', 4, 2, 4, 6],
        ['Nursing Research 1', 3, 0],
        ['Leadership and Management in Nursing', 3, 0],
        ['The Contemporary World', 3, 0],
    ],
    [
        ['Critical Care Nursing', 3, 2, 3, 6, 'NUR501'],
        ['Nursing Research 2', 3, 0, 3, 0, 'NUR503'],
        ['Disaster Nursing', 3, 0],
        ['Gerontological Nursing', 3, 1],
        ['Professional Nursing Practice', 3, 0],
    ],
    [
        ['Related Learning Experience 1', 0, 6, 0, 18, '3rd Year Standing'],
        ['Nursing Care Management 1', 3, 2],
        ['Health Education', 3, 0],
        ['Seminar in Nursing Practice', 3, 0],
    ],
    [
        ['Related Learning Experience 2', 0, 6, 0, 18, 'NUR701'],
        ['Nursing Care Management 2', 3, 2],
        ['Licensure Review Integration', 3, 0],
        ['Nursing Portfolio', 3, 0],
    ],
]);

$curriculum['Midwifery'] = buildCurriculum('MID', [
    [
        ['Anatomy and Physiology', 3, 1],
        ['Fundamentals of Midwifery', 3, 1],
        ['Basic Health Care 1', 2, 1],
        ['Communication Skills', 3, 0],
        ['Understanding the Self', 3, 0],
    ],
    [
        ['Maternal and Child Health', 3, 1, 3, 3],
        ['Obstetrics 1', 3, 1, 3, 3, 'MID102'],
        ['Basic Pharmacology', 3, 0],
        ['Nutrition', 3, 0],
        ['Physical Activities Toward Health and Fitness 1', 2, 0],
    ],
    [
        ['Obstetrics 2', 3, 1, 3, 3, 'MID202'],
        ['Normal Labor and Delivery Care', 2, 2, 2, 6],
        ['Community Health Service 1', 3, 1],
        ['Microbiology and Infection Control', 3, 1],
        ['Readings in Philippine History', 3, 0],
    ],
    [
        ['High Risk Pregnancy Care', 3, 1, 3, 3, 'MID301'],
        ['Newborn Care', 3, 1],
        ['Family Planning', 3, 0],
        ['Community Health Service 2', 3, 1, 3, 3, 'MID303'],
        ['Ethics', 3, 0],
    ],
    [
        ['Clinical Midwifery Practice 1', 1, 5, 1, 15, 'MID401'],
        ['Midwifery Research 1', 3, 0],
        ['Emergency Obstetric Care', 3, 1],
        ['Health Education', 3, 0],
        ['The Contemporary World', 3, 0],
    ],
    [
        ['Clinical Midwifery Practice 2', 1, 5, 1, 15, 'MID501'],
        ['Midwifery Research 2', 3, 0, 3, 0, 'MID502'],
        ['Professional Issues in Midwifery', 3, 0],
        ['Leadership and Management', 3, 0],
    ],
    [
        ['Advanced Clinical Practice 1', 0, 6, 0, 18, '3rd Year Standing'],
        ['Maternal Care Management', 3, 1],
        ['Seminar in Midwifery', 3, 0],
        ['Primary Health Care', 3, 0],
    ],
    [
        ['Advanced Clinical Practice 2', 0, 6, 0, 18, 'MID701'],
        ['Licensure Review Integration', 3, 0],
        ['Midwifery Portfolio', 3, 0],
        ['Entrepreneurship in Health Care', 3, 0],
    ],
]);

$curriculum['Civil Engineering'] = buildCurriculum('CEN', [
    [
        ['Calculus 1', 3, 0],
        ['Engineering Drawing', 1, 1, 1, 3],
        ['Chemistry for Engineers', 3, 1, 3, 3],
        ['Introduction to Civil Engineering', 3, 0],
        ['Purposive Communication', 3, 0],
    ],
    [
        ['Calculus 2', 3, 0, 3, 0, 'CEN101'],
        ['Physics for Engineers', 3, 1, 3, 3],
        ['Computer-Aided Drafting', 1, 1, 1, 3, 'CEN102'],
        ['Engineering Mechanics', 3, 0],
        ['Readings in Philippine History', 3, 0],
    ],
    [
        ['Statics of Rigid Bodies', 3, 0, 3, 0, 'CEN204'],
        ['Surveying 1', 2, 1],
        ['Differential Equations', 3, 0, 3, 0, 'CEN201'],
        ['Engineering Data Analysis', 3, 0],
        ['The Contemporary World', 3, 0],
    ],
    [
        ['Dynamics of Rigid Bodies', 3, 0, 3, 0, 'CEN301'],
        ['Surveying 2', 2, 1, 2, 3, 'CEN302'],
        ['Mechanics of Deformable Bodies', 3, 0, 3, 0, 'CEN301'],
        ['Fluid Mechanics', 3, 1],
        ['Ethics', 3, 0],
    ],
    [
        ['Structural Theory', 3, 0, 3, 0, 'CEN403'],
        ['Hydraulics', 3, 1, 3, 3, 'CEN404'],
        ['Geotechnical Engineering 1', 3, 1],
        ['Transportation Engineering', 3, 0],
        ['Construction Materials and Testing', 2, 1],
    ],
    [
        ['Reinforced Concrete Design', 3, 0, 3, 0, 'CEN501'],
        ['Steel Design', 3, 0, 3, 0, 'CEN501'],
        ['Geotechnical Engineering 2', 3, 1, 3, 3, 'CEN503'],
        ['Water Resources Engineering', 3, 0],
        ['CE Project Study 1', 3, 0],
    ],
    [
        ['CE Project Study 2', 3, 0, 3, 0, 'CEN605'],
        ['Construction Management', 3, 0],
        ['Environmental Engineering', 3, 0],
        ['Professional Practice and Ethics', 3, 0],
        ['CE Elective 1', 3, 0],
    ],
    [
        ['Civil Engineering Internship', 6, 0, 6, 0, '4th Year Standing'],
        ['CE Laws, Contracts, and Specifications', 3, 0],
        ['CE Elective 2', 3, 0],
        ['Licensure Review Integration', 3, 0],
    ],
]);

$curriculum['Psychology'] = buildCurriculum('PSY', [
    [
        ['Introduction to Psychology', 3, 0],
        ['Psychological Statistics 1', 3, 0],
        ['Biological Science', 3, 1],
        ['Understanding the Self', 3, 0],
        ['Purposive Communication', 3, 0],
    ],
    [
        ['Developmental Psychology', 3, 0, 3, 0, 'PSY101'],
        ['Theories of Personality', 3, 0],
        ['Psychological Statistics 2', 3, 0, 3, 0, 'PSY102'],
        ['Filipino Psychology', 3, 0],
        ['Readings in Philippine History', 3, 0],
    ],
    [
        ['Experimental Psychology', 2, 1, 2, 3, 'PSY203'],
        ['Social Psychology', 3, 0],
        ['Cognitive Psychology', 3, 0],
        ['Abnormal Psychology', 3, 0],
        ['The Contemporary World', 3, 0],
    ],
    [
        ['Psychological Assessment', 2, 1, 2, 3, 'PSY301'],
        ['Industrial / Organizational Psychology', 3, 0],
        ['Educational Psychology', 3, 0],
        ['Research Methods in Psychology', 3, 0],
        ['Ethics', 3, 0],
    ],
    [
        ['Counseling Psychology', 3, 0],
        ['Clinical Psychology', 3, 0, 3, 0, 'PSY304'],
        ['Psychometrics', 2, 1],
        ['Research in Psychology 1', 3, 0, 3, 0, 'PSY404'],
        ['Psychology Elective 1', 3, 0],
    ],
    [
        ['Research in Psychology 2', 3, 0, 3, 0, 'PSY504'],
        ['Group Process and Dynamics', 3, 0],
        ['Health Psychology', 3, 0],
        ['Psychology Elective 2', 3, 0],
        ['Seminar in Psychology', 3, 0],
    ],
    [
        ['Practicum 1', 0, 6, 0, 18, '3rd Year Standing'],
        ['Case Analysis in Psychology', 3, 0],
        ['Psychology Elective 3', 3, 0],
        ['Professional Ethics in Psychology', 3, 0],
    ],
    [
        ['Practicum 2', 0, 6, 0, 18, 'PSY701'],
        ['Board Review Integration', 3, 0],
        ['Psychology Portfolio', 3, 0],
        ['Career Preparation in Psychology', 3, 0],
    ],
]);

$curriculum['Life Science'] = buildCurriculum('LFS', [
    [
        ['General Biology 1', 3, 1],
        ['General Chemistry 1', 3, 1],
        ['College Algebra', 3, 0],
        ['Science, Technology and Society', 3, 0],
        ['Purposive Communication', 3, 0],
    ],
    [
        ['General Biology 2', 3, 1, 3, 3, 'LFS101'],
        ['General Chemistry 2', 3, 1, 3, 3, 'LFS102'],
        ['Biostatistics', 3, 0],
        ['Environmental Science', 3, 0],
        ['Readings in Philippine History', 3, 0],
    ],
    [
        ['Cell Biology', 3, 1, 3, 3, 'LFS201'],
        ['Organic Chemistry', 3, 1, 3, 3, 'LFS202'],
        ['Genetics', 3, 1],
        ['Ecology', 3, 1],
        ['The Contemporary World', 3, 0],
    ],
    [
        ['Microbiology', 3, 1, 3, 3, 'LFS301'],
        ['Biochemistry', 3, 1, 3, 3, 'LFS302'],
        ['Evolutionary Biology', 3, 0],
        ['Taxonomy and Systematics', 2, 1],
        ['Ethics', 3, 0],
    ],
    [
        ['Molecular Biology', 3, 1, 3, 3, 'LFS402'],
        ['Plant Physiology', 2, 1],
        ['Animal Physiology', 2, 1],
        ['Research Methods in Life Science', 3, 0],
        ['Life Science Elective 1', 3, 0],
    ],
    [
        ['Biotechnology', 3, 1, 3, 3, 'LFS501'],
        ['Conservation Biology', 3, 0],
        ['Research in Life Science 1', 3, 0, 3, 0, 'LFS504'],
        ['Life Science Elective 2', 3, 0],
        ['Laboratory Management', 2, 1],
    ],
    [
        ['Research in Life Science 2', 3, 0, 3, 0, 'LFS603'],
        ['Field Biology', 1, 2],
        ['Life Science Elective 3', 3, 0],
        ['Seminar in Life Science', 3, 0],
        ['Bioethics', 3, 0],
    ],
    [
        ['Life Science Internship', 6, 0, 6, 0, '4th Year Standing'],
        ['Scientific Writing and Publication', 3, 0],
        ['Life Science Portfolio', 3, 0],
        ['Career Preparation in Life Science', 3, 0],
    ],
]);

$programStmt = $pdo->prepare('INSERT INTO programs (name) VALUES (:name) ON DUPLICATE KEY UPDATE name = VALUES(name)');
$programIdStmt = $pdo->prepare('SELECT id FROM programs WHERE name = :name LIMIT 1');
$courseStmt = $pdo->prepare(
    'INSERT INTO courses
        (program_id, code, title, units, credit_lec, credit_lab, contact_lec, contact_lab, prerequisite, semester)
     VALUES
        (:program_id, :code, :title, :units, :credit_lec, :credit_lab, :contact_lec, :contact_lab, :prerequisite, :semester)
     ON DUPLICATE KEY UPDATE
        program_id = VALUES(program_id),
        title = VALUES(title),
        units = VALUES(units),
        credit_lec = VALUES(credit_lec),
        credit_lab = VALUES(credit_lab),
        contact_lec = VALUES(contact_lec),
        contact_lab = VALUES(contact_lab),
        prerequisite = VALUES(prerequisite),
        semester = VALUES(semester)'
);

$inserted = 0;

foreach ($curriculum as $programName => $courses) {
    $programStmt->execute(['name' => $programName]);
    $programIdStmt->execute(['name' => $programName]);
    $programId = (int) $programIdStmt->fetchColumn();

    foreach ($courses as $course) {
        [$semester, $code, $title, $creditLec, $creditLab, $contactLec, $contactLab, $prerequisite] = $course;
        $courseStmt->execute([
            'program_id' => $programId,
            'code' => $code,
            'title' => $title,
            'units' => $creditLec + $creditLab,
            'credit_lec' => $creditLec,
            'credit_lab' => $creditLab,
            'contact_lec' => $contactLec,
            'contact_lab' => $contactLab,
            'prerequisite' => $prerequisite !== '' ? $prerequisite : null,
            'semester' => $semester,
        ]);
        $inserted++;
    }
}

echo "Seeded or updated {$inserted} curriculum courses.\n";
