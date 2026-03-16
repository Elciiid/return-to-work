<?php
/**
 * Helper function to get employee profile photo URL
 * @param string $employee_number The employee ID/number
 * @return string The photo URL or empty string if not found
 */
function getEmployeePhotoUrl($employee_number)
{
    if (empty($employee_number)) {
        return '';
    }

    // Base URL for employee photos
    $base_url = 'http://10.2.0.8/lrnph/emp_photos/';

    // Try common image extensions
    $extensions = ['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'];

    // Return the first extension format (browser will handle 404 if not found)
    // We'll use JavaScript to handle fallback on client side
    return $base_url . htmlspecialchars($employee_number) . '.jpg';
}

/**
 * Generate an img tag with fallback for employee photo
 * @param string $employee_number The employee ID/number
 * @param string $classes CSS classes for the img tag
 * @param string $alt Alt text (defaults to employee number)
 * @return string HTML img tag with fallback icon
 */
function getEmployeePhotoImg($employee_number, $classes = '', $alt = '')
{
    if (empty($employee_number)) {
        return '<i class="fa-solid fa-user text-gray-400 text-2xl"></i>';
    }

    $employee_id = htmlspecialchars($employee_number);
    $alt_text = $alt ?: 'Employee ' . $employee_id;
    $classes_attr = $classes ? ' class="' . htmlspecialchars($classes) . '"' : '';

    // Base URL for employee photos
    $base_url = 'http://10.2.0.8/lrnph/emp_photos/';

    // Create img tag with JavaScript fallback that tries multiple extensions
    $js_function = "tryPhotoExtensions('$employee_id', this)";

    return '<div class="relative w-full h-full flex items-center justify-center"><img src="' . $base_url . $employee_id . '.jpg" alt="' . htmlspecialchars($alt_text) . '"' . $classes_attr . ' onerror="' . $js_function . '" class="relative z-10" /><i class="fa-solid fa-user text-gray-400 text-2xl absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 z-0 hidden"></i></div>';
}
?>