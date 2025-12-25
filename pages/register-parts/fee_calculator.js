/**
 * Fee Calculator Module - CORRECTED for January 2026
 * Calculates monthly fees based on:
 * - Number of classes enrolled
 * - Number of sessions per class in the month
 * - Per-session pricing: 1st class RM30, 2nd class RM27, 3rd class RM24, 4th class RM21
 * 
 * IMPORTANT RULE: Classes are SORTED by session count (descending)
 * Classes with FEWER sessions get LOWER pricing (last position)
 * 
 * EXAMPLES:
 * - Wed (3), Tue (3), Fri (3), Sun (2) → Sorted as: 3,3,3,2 → Pricing: RM30,RM27,RM24,RM21
 * - Result: (3×RM30) + (3×RM27) + (3×RM24) + (2×RM21) = RM90 + RM81 + RM72 + RM42 = RM285
 */

const FeeCalculator = {
    // Per-session pricing for January 2026
    // 1st class: RM30 per session
    // 2nd class: RM27 per session
    // 3rd class: RM24 per session
    // 4th class: RM21 per session
    sessionPricing: [30, 27, 24, 21],

    /**
     * Get price per session for a specific class position
     * @param {number} classPosition - 0-indexed position (0=first class, 1=second class, etc.)
     */
    getPricePerSession: function(classPosition) {
        return this.sessionPricing[classPosition] || this.sessionPricing[this.sessionPricing.length - 1];
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
     * Calculate total monthly fee - CORRECTED FORMULA WITH SORTING
     * Classes are SORTED by session count (descending) before pricing
     * This ensures classes with fewer sessions get the lower pricing
     * @param {Array} classesData - Array of class data with session counts
     * @returns {Object} Fee breakdown
     */
    calculateMonthlyFee: function(classesData) {
        // ✅ SORT classes by session count DESCENDING (most sessions first)
        // This ensures classes with fewer sessions get the lower price (last position)
        const sortedClasses = [...classesData].sort((a, b) => b.classCount - a.classCount);
        
        let totalFee = 0;
        let breakdown = [];
        let totalSessions = 0;
        
        // Calculate fee for each class based on its SORTED position
        sortedClasses.forEach((classData, index) => {
            const pricePerSession = this.getPricePerSession(index);
            const sessionCount = classData.classCount;
            const classFee = pricePerSession * sessionCount;
            
            totalFee += classFee;
            totalSessions += sessionCount;
            
            breakdown.push({
                position: index + 1,
                className: classData.className,
                schedule: classData.schedule,
                pricePerSession: pricePerSession,
                sessionCount: sessionCount,
                classFee: classFee,
                formatted: {
                    pricePerSession: `RM${pricePerSession.toFixed(2)}`,
                    classFee: `RM${classFee.toFixed(2)}`
                }
            });
        });
        
        console.log('💰 Fee Calculation (Sorted by Sessions):');
        breakdown.forEach(item => {
            console.log(`   ${item.position}. ${item.className}: ${item.sessionCount} sessions × RM${item.pricePerSession} = RM${item.classFee}`);
        });
        console.log(`   TOTAL: ${totalSessions} sessions = RM${totalFee}`);
        
        return {
            numClasses: classesData.length,
            totalSessions: totalSessions,
            breakdown: breakdown,
            totalFee: totalFee,
            formatted: {
                totalFee: `RM${totalFee.toFixed(2)}`
            }
        };
    },

    /**
     * Display fee breakdown in the UI - UPDATED WITH SORTING INFO
     * @param {Object} feeData - Fee calculation data
     * @param {Array} classesData - Available dates data for each class
     */
    displayFeeBreakdown: function(feeData, classesData) {
        const container = document.getElementById('feeBreakdownContainer');
        if (!container) return;

        let html = `
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator"></i> Monthly Fee Calculation (January 2026)</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle"></i> <strong>Pricing Formula:</strong><br>
                        Classes sorted by session count (most → least)<br>
                        1st: RM30/session | 2nd: RM27/session | 3rd: RM24/session | 4th: RM21/session
                    </div>
                    
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
                            <strong>Total Sessions This Month:</strong>
                        </div>
                        <div class="col-md-6 text-end">
                            ${feeData.totalSessions} session${feeData.totalSessions > 1 ? 's' : ''}
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6 class="mb-3"><i class="fas fa-list"></i> Per-Class Breakdown (Sorted by Sessions):</h6>
        `;

        // Display breakdown for each class (already sorted)
        feeData.breakdown.forEach(item => {
            html += `
                <div class="card mb-2" style="background-color: #f8f9fa;">
                    <div class="card-body py-2">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <strong>${item.position}${this.getOrdinalSuffix(item.position)} Class:</strong> ${item.className}<br>
                                <small class="text-muted">${item.schedule} - ${item.sessionCount} session${item.sessionCount > 1 ? 's' : ''}</small>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="text-muted">${item.sessionCount} × ${item.formatted.pricePerSession} = </span>
                                <strong class="text-primary">${item.formatted.classFee}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += `
                    <hr class="mt-3">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="mb-0"><strong>Total Monthly Fee:</strong></h5>
                        </div>
                        <div class="col-md-6 text-end">
                            <h3 class="text-primary mb-0">${feeData.formatted.totalFee}</h3>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Add available dates for each class (show in sorted order)
        if (classesData && classesData.length > 0) {
            // Sort classesData by classCount to match the breakdown
            const sortedClassesData = [...classesData].sort((a, b) => b.classCount - a.classCount);
            
            html += `
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-calendar-check"></i> Available Class Dates (Sorted by Sessions)</h6>
                    </div>
                    <div class="card-body">
            `;

            sortedClassesData.forEach((classData, index) => {
                const pricePerSession = this.getPricePerSession(index);
                html += `
                    <div class="mb-3">
                        <h6 class="text-primary">${index + 1}${this.getOrdinalSuffix(index + 1)} Class: ${classData.className} - ${classData.schedule}</h6>
                        <p class="mb-2">
                            <strong>${classData.classCount} sessions available</strong>
                            <span class="badge bg-primary ms-2">RM${pricePerSession}/session</span>
                        </p>
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

                html += '</div>';
                if (index < sortedClassesData.length - 1) {
                    html += '<hr class="my-3">';
                }
            });

            html += `
                    </div>
                </div>
            `;
        }

        container.innerHTML = html;
    },

    /**
     * Helper function to get ordinal suffix (1st, 2nd, 3rd, 4th)
     */
    getOrdinalSuffix: function(num) {
        const suffixes = ['th', 'st', 'nd', 'rd'];
        const v = num % 100;
        return (suffixes[(v - 20) % 10] || suffixes[v] || suffixes[0]);
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

            // Calculate fee using CORRECTED formula with SORTING
            const feeData = this.calculateMonthlyFee(classesData);

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