<?php
/**
 * Helper function to get employee profile photo URL
 * @param string $employee_number The employee ID/number
 * @param string $fullname The full name of the employee to generate initials
 * @return string The photo URL
 */
function getEmployeePhotoUrl($employee_number, $fullname = '')
{
    // For demo: Use UI-Avatars for all profiles
    $name = !empty($fullname) ? urlencode($fullname) : urlencode($employee_number);
    return "https://ui-avatars.com/api/?name={$name}&background=random&color=fff&size=128&bold=true";
}

/**
 * Generate an img tag with fallback for employee photo
 * @param string $employee_number The employee ID/number
 * @param string $classes CSS classes for the img tag
 * @param string $alt Alt text (defaults to employee number)
 * @return string HTML img tag
 */
function getEmployeePhotoImg($employee_number, $classes = '', $alt = '', $fullname = '')
{
    $photo_url = getEmployeePhotoUrl($employee_number, $fullname);
    $classes_attr = $classes ? ' class="' . htmlspecialchars($classes) . '"' : '';
    $alt_text = htmlspecialchars($alt ?: $fullname ?: 'Employee ' . $employee_number);

    return '<img src="' . $photo_url . '" alt="' . $alt_text . '"' . $classes_attr . ' />';
}
?>