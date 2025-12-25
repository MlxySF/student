// ========================================
// CALCULATE CLASSES FOR A SINGLE SCHEDULE - UPDATED TO USE CURRENT MONTH
// ========================================
function calculateClassesForSchedule(scheduleText, holidays) {
    // Parse schedule to extract day
    let dayOfWeek = null;
    
    if (scheduleText.includes('Wed') || scheduleText.includes('Wednesday')) {
        dayOfWeek = 3; // Wednesday
    } else if (scheduleText.includes('Sun') || scheduleText.includes('Sunday')) {
        dayOfWeek = 0; // Sunday
    } else if (scheduleText.includes('Tue') || scheduleText.includes('Tuesday')) {
        dayOfWeek = 2; // Tuesday
    } else if (scheduleText.includes('Fri') || scheduleText.includes('Friday')) {
        dayOfWeek = 5; // Friday
    }
    
    if (dayOfWeek === null) {
        console.warn('Could not parse day from schedule:', scheduleText);
        return 4; // Default to 4 classes per month if parsing fails
    }
    
    // Get current date
    const now = new Date();
    const currentMonth = now.getMonth(); // 0-11
    const currentYear = now.getFullYear();
    
    // Calculate first and last day of current month
    const startDate = new Date(currentYear, currentMonth, 1);
    const endDate = new Date(currentYear, currentMonth + 1, 0); // Last day of month
    
    // Generate all dates for this specific day of week in current month
    const allDates = [];
    
    for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
        if (d.getDay() === dayOfWeek) {
            const dateStr = d.toISOString().split('T')[0];
            allDates.push(dateStr);
        }
    }
    
    // Filter out holidays
    const validDates = allDates.filter(date => !holidays.includes(date));
    
    const monthName = startDate.toLocaleString('en-US', { month: 'long', year: 'numeric' });
    console.log(`📅 ${scheduleText} (${monthName}): ${allDates.length} total, ${validDates.length} after holidays`);
    
    return validDates.length;
}
