	$(document).ready(function(){
                /* -------------------------------------------------------------------------*
                 * MOBILE MENU
                 * -------------------------------------------------------------------------*/
                        $(".button-collapse").sideNav();
                /* -------------------------------------------------------------------------*
                 * DROPDOWN MENU
                 * -------------------------------------------------------------------------*/
		$('.dropdown-button').dropdown({
			inDuration: 425,
			outDuration: 225,
			constrain_width: false, // Does not change width of dropdown to that of the activator
			hover: true, // Activate on hover
			gutter: 0, // Spacing from edge
			belowOrigin: true, // Displays dropdown below the button
			alignment: 'left' // Displays dropdown with edge aligned to the left of button
			}
		  );
		/* -------------------------------------------------------------------------*
		 * FORMS
		 * -------------------------------------------------------------------------*/
		$('.modal').modal();
                /* ------------------------------------------------------------------------- *
		 * BACK TO TOP BUTTON
		 * ------------------------------------------------------------------------- */
                var backToTop = {
                    $el: $('.back-to-top'),
                    $btn: $('.back-to-top button'),
                    show: function () {
                        return ( $(window).scrollTop() > 1 ) ? backToTop.$el.addClass('show') : backToTop.$el.removeClass('show');
                    }
                };

                backToTop.show();

                backToTop.$btn.on('click', function() {
                    $("html, body").animate({scrollTop: 0}, 500);
                });
		
		/* -------------------------------------------------------------------------*
		 * ON SCROLL
		 * -------------------------------------------------------------------------*/
                $(window).on('scroll', function () {
                    backToTop.show();
                    });
                });
		/* ------------------------------------------------------------------------- *
		 * PRELOADER
		 * ------------------------------------------------------------------------- */
                $(window).on('load', function () {
                    $('body').addClass('loaded');
                });
                /* ------------------------------------------------------------------------- *
                 * 
                 * ------------------------------------------------------------------------- */
                $(document).ready(function() {
                    $('select').material_select();
                  });