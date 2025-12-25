function calculateFees() {
    const schedules = document.querySelectorAll('input[name="sch"]:checked');
    const scheduleCount = schedules.length;
    
    if (scheduleCount === 0) {
        return { classCount: 0, totalFee: 0, holidayDeduction: 0 };
    }
    
    // Base prices
    let baseFee = 0;
    if (scheduleCount === 1) baseFee = 120;
    else if (scheduleCount === 2) baseFee = 200;
    else if (scheduleCount === 3) baseFee = 280;
    else if (scheduleCount >= 4) baseFee = 320;
    
    // Deduction rates per holiday per schedule
    const deductionRates = {
        1: 30,    // RM30 per holiday for 1 schedule
        2: 25,    // RM25 per holiday for 2 schedules
        3: 23.33, // RM23.33 per holiday for 3 schedules
        4: 20     // RM20 per holiday for 4+ schedules
    };
    
    const deductionRate = deductionRates[scheduleCount] || deductionRates[4];
    
    // Get current date info
    const now = new Date();
    const currentMonth = now.getMonth();
    const currentYear = now.getFullYear();
    
    // Count total holidays that affect selected schedules
    let totalMissedClasses = 0;
    
    schedules.forEach(scheduleCheckbox => {
        const scheduleText = scheduleCheckbox.value;
        
        // Parse day of week from schedule
        let dayOfWeek = null;
        if (scheduleText.includes('Wed') || scheduleText.includes('Wednesday')) dayOfWeek = 3;
        else if (scheduleText.includes('Sun') || scheduleText.includes('Sunday')) dayOfWeek = 0;
        else if (scheduleText.includes('Tue') || scheduleText.includes('Tuesday')) dayOfWeek = 2;
        else if (scheduleText.includes('Fri') || scheduleText.includes('Friday')) dayOfWeek = 5;
        
        if (dayOfWeek === null) return;
        
        // Get all dates for this day in current month
        const startDate = new Date(currentYear, currentMonth, 1);
        const endDate = new Date(currentYear, currentMonth + 1, 0);
        const allDates = [];
        
        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
            if (d.getDay() === dayOfWeek) {
                const dateStr = d.getFullYear() + '-' + 
                                String(d.getMonth() + 1).padStart(2, '0') + '-' + 
                                String(d.getDate()).padStart(2, '0');
                allDates.push(dateStr);
            }
        }
        
        // Count how many holidays affect this schedule
        const missedClasses = allDates.filter(date => classHolidays.includes(date)).length;
        totalMissedClasses += missedClasses;
    });
    
    // Calculate total deduction
    const holidayDeduction = Math.round(totalMissedClasses * deductionRate * 100) / 100;
    
    // Calculate final fee
    const totalFee = Math.max(0, baseFee - holidayDeduction);
    
    // Get actual class counts
    const { totalClasses } = calculateActualClassCounts();
    
    console.log(`💰 Fee Calculation:`);
    console.log(`   Base Fee: RM${baseFee}`);
    console.log(`   Schedules: ${scheduleCount}`);
    console.log(`   Deduction Rate: RM${deductionRate} per holiday`);
    console.log(`   Total Missed Classes: ${totalMissedClasses}`);
    console.log(`   Holiday Deduction: RM${holidayDeduction}`);
    console.log(`   Final Fee: RM${totalFee}`);
    
    return { 
        classCount: totalClasses, 
        totalFee: totalFee,
        baseFee: baseFee,
        holidayDeduction: holidayDeduction,
        missedClasses: totalMissedClasses
    };
}
