/**
 * Fee Calculator Module
 * Calculates monthly fees based on:
 * - Number of classes enrolled
 * - Available dates in the month (excluding holidays)
 * - Pricing tiers
 */

const FeeCalculator = {
    // Pricing structure based on number of classes enrolled
    pricing: {
        1: 30.00,  // RM30 per class if 1 class enrolled
        2: 25.00,  // RM25 per class if 2 classes enrolled
        3: 23.33,  // RM23.33 per class if 3 classes enrolled
        4: 20.00   // RM20 per class if 4+ classes enrolled
    },

    /**
     * Get price per class based on number of classes enrolled
     */
    getPricePerClass: function(numClasses) {
        if (numClasses >= 4) return this.pricing[4];
        return this.pricing[numClasses] || this.pricing[1];
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
                
                const response = await fetch(
                    `../admin_pages/api/get_available_dates.php?month=${month}&year=${year}&class_schedule=${dayName}`
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
     * Calculate total monthly fee
     * @param {number} numClasses - Number of classes enrolled
     * @param {number} classesPerMonth - Available classes per month
     * @returns {Object} Fee breakdown
     */
    calculateMonthlyFee: function(numClasses, classesPerMonth) {
        const pricePerClass = this.getPricePerClass(numClasses);
        const totalFee = pricePerClass * classesPerMonth;
        
        return {
            numClasses: numClasses,
            pricePerClass: pricePerClass,
            classesPerMonth: classesPerMonth,
            totalFee: totalFee,
            formatted: {
                pricePerClass: `RM${pricePerClass.toFixed(2)}`,
                totalFee: `RM${totalFee.toFixed(2)}`
            }
        };
    },

    /**
     * Display fee breakdown in the UI
     * @param {Object} feeData - Fee calculation data
     * @param {Array} classesData - Available dates data for each class
     */
    displayFeeBreakdown: function(feeData, classesData) {
        const container = document.getElementById('feeBreakdownContainer');
        if (!container) return;

        let html = `
            <div class="card border-primary mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-calculator"></i> Monthly Fee Calculation</h5>
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
                            <strong>Price Per Class:</strong>
                        </div>
                        <div class="col-md-6 text-end">
                            ${feeData.formatted.pricePerClass}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Available Classes This Month:</strong>
                        </div>
                        <div class="col-md-6 text-end">
                            ${feeData.classesPerMonth} day${feeData.classesPerMonth > 1 ? 's' : ''}
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total Monthly Fee:</strong>
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

            // Calculate fee based on number of classes enrolled
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
