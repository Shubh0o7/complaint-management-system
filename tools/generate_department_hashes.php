<?php
$passwords = [
    'manager@campus.edu' => 'Manager@1234',
    'officer@campus.edu' => 'Officer@1234',
    'infrastructure.manager@campus.edu' => 'InfraManager@1234',
    'infrastructure.officer@campus.edu' => 'InfraOfficer@1234',
    'academic.manager@campus.edu' => 'AcademicManager@1234',
    'academic.officer@campus.edu' => 'AcademicOfficer@1234',
    'student.manager@campus.edu' => 'StudentManager@1234',
    'student.officer@campus.edu' => 'StudentOfficer@1234',
];
foreach ($passwords as $email => $password) {
    echo $email . "\t" . password_hash($password, PASSWORD_DEFAULT) . "\n";
}
?>
