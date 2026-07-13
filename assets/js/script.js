(function($) {
    'use strict';

    var GhanaDistricts = {
        init: function() {
            this.bindEvents();
            this.handleInitialState();
        },

        bindEvents: function() {
            // Use event delegation for dynamic support (essential for CF7)
            $(document).on('change', '.ghana-region-select', this.handleRegionChange.bind(this));
            
            // Special trigger for CF7 form initialization if needed
            $(document).on('wpcf7mailsent wpcf7submit', function() {
                // Re-initialize if CF7 resets the form
                GhanaDistricts.handleInitialState();
            });
        },

        handleInitialState: function() {
            var self = this;
            $('.ghana-region-select').each(function() {
                var $el = $(this);
                if ($el.val()) {
                    self.handleRegionChange({ currentTarget: $el[0] });
                }
            });
        },

        handleRegionChange: function(e) {
            var $regionSelect = $(e.currentTarget);
            var selectedRegion = $regionSelect.val();
            var group = $regionSelect.data('group') || 'default';
            var $districtSelect = this.findDistrictDropdown($regionSelect, group);
            var data = window.ghanaDistrictsData;

            if (!$districtSelect.length) {
                return;
            }

            this.resetDistrictDropdown($districtSelect, data);

            if (selectedRegion && data && data.districts[selectedRegion]) {
                this.populateDistricts($districtSelect, data.districts[selectedRegion], data);
            }
        },

        findDistrictDropdown: function($regionSelect, group) {
            // First, try to find in the same form/wrapper by group
            var $form = $regionSelect.closest('form, .wpcf7-form, .ghana-regions-wrapper');
            var $district = $form.find('.ghana-district-select[data-group="' + group + '"]');
            
            // If not found, try finding globally by group
            if (!$district.length) {
                $district = $('.ghana-district-select[data-group="' + group + '"]');
            }
            
            // Fallback: look for the next district dropdown in the DOM
            if (!$district.length) {
                $district = $regionSelect.closest('.ghana-regions-wrapper, .wpcf7-form-control-wrap').nextAll().find('.ghana-district-select').first();
            }
            
            return $district;
        },

        resetDistrictDropdown: function($districtSelect, data) {
            var placeholder = (data && data.strings) ? data.strings.select_region_first : 'Select Region First';
            $districtSelect.empty().append($('<option>', { value: '', text: placeholder })).prop('disabled', true);
        },

        populateDistricts: function($districtSelect, districts, data) {
            var placeholder = (data && data.strings) ? data.strings.select_district : 'Select District';
            $districtSelect.empty().append($('<option>', { value: '', text: placeholder }));
            $.each(districts, function(index, district) {
                // Keep the district name as the value for better compatibility with form submissions
                $districtSelect.append($('<option>', { value: district, text: district }));
            });
            $districtSelect.prop('disabled', false);
        }
    };

    $(function() {
        GhanaDistricts.init();
    });
})(jQuery);
