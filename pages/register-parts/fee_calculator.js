/**
 * Fee Calculator Module - Updated for January 2026
 * Calculates monthly fees based on:
 * - Number of classes enrolled (new tiered pricing)
 * - Available dates in the month (excluding holidays)
 * - New pricing tiers for 2026
 */

const FeeCalculator = {
    // NEW PRICING STRUCTURE FOR JANUARY 2026
    // Scenario 1: 1 class = RM30
    // Scenario 2: 2 classes = RM30 + RM27 = RM57
    // Scenario 3: 3 classes = RM30 + RM27 + RM24 = RM81
    // Scenario 4: 4 classes = RM30 + RM27 + RM24 + RM21 = RM102
    pricing: {
        1: { total: 30, perClass: 30.00 },      // 1 class: RM30 total
        2: { total: 57, perClass: 28.50 },      // 2 classes: RM57 total (avg RM28.50/class)
        3: { total: 81, perClass: 27.00 },      // 3 classes: RM81 total (avg RM27/class)
        4: { total: 102, perClass: 25.50 }      // 4 classes: RM102 total (avg RM25.50/class)
    },

    /**
     * Get pricing info based on number of classes enrolled
     */
    getPricingInfo: function(numClasses) {
        // Cap at 4 classes maximum
        const classCount = Math.min(numClasses, 4);
        return this.pricing[classCount] || this.pricing[1];
    },

    /**
     * Fetch available dates for selected classes
     * @param {Array} selectedClasses - Array of class schedule objects
     * @param {number} month - Month number (1-12)
     * @param {number} year - Year
     * @returns {Promise} Promise resolving to dates data
     */
    fetchAvailableDates: async function(selectedClasses, month, year) {
        try {
            const results = [];
            
            for (const classSchedule of selectedClasses) {
                // Extract day name from schedule (e.g., "Monday 4:00 PM" -> "Monday")
                const dayName = classSchedule.schedule.split(' ')[0];
                
                // Use the correct API path - go up to root then to admin_pages
                const response = await fetch(
                    `../../admin_pages/api/get_available_dates.php?month=${month}&year=${year}&class_schedule=${dayName}`
                );
                
                if (!response.ok) {
                    throw new Error('Failed to fetch available dates');
                }
                
                const data = await response.json();
                
                if (data.success) {
                    results.push({
                        classId: classSchedule.id,
                        className: classSchedule.name,
                        schedule: classSchedule.schedule,
                        availableDates: data.available_dates,
                        classCount: data.class_count
                    });
                }
            }
            
            return results;
        } catch (error) {
            console.error('Error fetching available dates:', error);
            throw error;
        }
    },

    /**
     * Calculate total monthly fee - UPDATED FOR 2026
     * @param {number} numClasses - Number of classes enrolled
     * @param {number} classesPerMonth - Available classes per month
     * @returns {Object} Fee breakdown
     */
    calculateMonthlyFee: function(numClasses, classesPerMonth) {
        // Get the base pricing for this number of classes
        const pricingInfo = this.getPricingInfo(numClasses);
        
        // Calculate total fee = base rate per class × number of available class days
        const totalFee = pricingInfo.perClass * classesPerMonth;
        
        return {
            numClasses: numClasses,
            baseTotal: pricingInfo.total,  // Base total for reference (e.g., RM57 for 2 classes)
            pricePerClass: pricingInfo.perClass,
            classesPerMonth: classesPerMonth,
            totalFee: totalFee,
            formatted: {
                baseTotal: `RM${pricingInfo.total.toFixed(2)}`,
                pricePerClass: `RM${pricingInfo.perClass.toFixed(2)}`,
                totalFee: `RM${totalFee.toFixed(2)}`
            }
        };
    },

    /**
     * Display fee breakdown in the UI - UPDATED FOR 2026
     * @param {Object} feeData - Fee calculation data
     * @param {Array} classesData - Available dates data for each class
     */
    displayFeeBreakdown: function(feeData, classesData) {
        const container = document.getElementById('feeBreakdownContainer');
        if (!container) return;

        // Calculate the pricing breakdown based on number of classes
        let pricingExplanation = '';
        switch(feeData.numClasses) {
            case 1:
                pricingExplanation = '<small class="text-muted">(RM30 per class)</small>';
                break;
            case 2:
                pricingExplanation = '<small class="text-muted">(RM30 + RM27 = RM57 base)</small>';
                break;
            case 3:
                pricingExplanation = '<small class="text-muted">(RM30 + RM27 + RM24 = RM81 base)</small>';
                break;
            case 4:
                pricingExplanation = '<small class="text-muted">(RM30 + RM27 + RM24 + RM21 = RM102 base)</small>';
                break;
        }

        let html = `
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator"></i> Monthly Fee Calculation (January 2026)</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Number of Classes Enrolled:</strong>
                        </div>
                        <div class="col-md-6 text-end">
                            ${feeData.numClasses} class${feeData.numClasses > 1 ? 'es' : ''}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Base Rate Per Class Day:</strong><br>
                            ${pricingExplanation}
                        </div>
                        <div class="col-md-6 text-end">
                            ${feeData.formatted.pricePerClass}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Available Class Days This Month:</strong>
                        </div>
                        <div class="col-md-6 text-end">
                            ${feeData.classesPerMonth} day${feeData.classesPerMonth > 1 ? 's' : ''}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total Monthly Fee:</strong><br>
                            <small class="text-muted">(${feeData.formatted.pricePerClass} × ${feeData.classesPerMonth} days)</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <h4 class="text-primary mb-0">${feeData.formatted.totalFee}</h4>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add available dates for each class
        if (classesData && classesData.length > 0) {
            html += `
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-calendar-check"></i> Available Class Dates</h6>
                    </div>
                    <div class="card-body">
            `;

            classesData.forEach(classData => {
                html += `
                    <div class="mb-3">
                        <h6 class="text-primary">${classData.className} - ${classData.schedule}</h6>
                        <p class="mb-2"><strong>${classData.classCount} classes available</strong></p>
                `;

                if (classData.availableDates.length > 0) {
                    html += '<div class="d-flex flex-wrap gap-2">';
                    classData.availableDates.forEach(date => {
                        html += `
                            <span class="badge bg-light text-dark border">
                                ${date.formatted}
                            </span>
                        `;
                    });
                    html += '</div>';
                } else {
                    html += '<p class="text-muted">No classes available this month</p>';
                }

                html += '</div><hr class="my-3">';
            });

            html += `
                    </div>
                </div>
            `;
        }

        container.innerHTML = html;
    },

    /**
     * Main function to calculate and display fees
     * @param {Array} selectedClasses - Array of selected class objects
     * @param {number} month - Month (optional, defaults to current)
     * @param {number} year - Year (optional, defaults to current)
     */
    calculateAndDisplay: async function(selectedClasses, month = null, year = null) {
        if (!selectedClasses || selectedClasses.length === 0) {
            const container = document.getElementById('feeBreakdownContainer');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Please select at least one class to see the fee calculation.
                    </div>
                `;
            }
            return;
        }

        try {
            // Use current month/year if not provided
            const now = new Date();
            month = month || (now.getMonth() + 1);
            year = year || now.getFullYear();

            // Show loading state
            const container = document.getElementById('feeBreakdownContainer');
            if (container) {
                container.innerHTML = `
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Calculating fees...</p>
                    </div>
                `;
            }

            // Fetch available dates for each selected class
            const classesData = await this.fetchAvailableDates(selectedClasses, month, year);

            // Calculate average or use the first class's count (they should all be the same month)
            const classesPerMonth = classesData[0]?.classCount || 0;

            // Calculate fee based on number of classes enrolled (NEW 2026 PRICING)
            const feeData = this.calculateMonthlyFee(selectedClasses.length, classesPerMonth);

            // Display the results
            this.displayFeeBreakdown(feeData, classesData);

        } catch (error) {
            console.error('Error calculating fees:', error);
            const container = document.getElementById('feeBreakdownContainer');
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> Error calculating fees. Please try again.
                    </div>
                `;
            }
        }
    }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FeeCalculator;
}