//  AOS.init({
//      duration: 800,
//      easing: 'slide',
//      once: true
//  });

 jQuery(document).ready(function($) {

     "use strict";

     var slider = function() {
           
         $('.threeCollection').owlCarousel({
            center: false,
            items: 1,
            loop: true,
            margin: 20,
            nav: false,
            autoplay: true,
            dots: true,
            navText: ['<span class="icon-arrow_back">', '<span class="icon-arrow_forward">'],
            responsive: {
                600: {
                    margin: 20,
                    items: 1,
                    loop: false
                },
                1000: {
                    margin: 20,
                    items: 2,
                    loop: false
                },
                1200: {
                    margin: 20,
                    items: 2,
                    loop: false
                }
            }
        });
		
		$('.oneCollection').owlCarousel({
            center: false,
            items: 1,
            loop: true,
            margin: 0,
            nav: false,
            autoplay: true,
            dots: true,
            navText: ['<span class="icon-arrow_back">', '<span class="icon-arrow_forward">'],
            responsive: {
                600: {
                    margin: 0,
                    items: 1
                },
                1000: {
                    margin: 0,
                    items: 1
                },
                1200: {
                    margin: 0,
                    items: 1
                }
            }
        });

 
     };
     slider();
	 
	  
         
         
     
	 $("#mngl-video").on('hidden.bs.modal', function (e) {
			$("#mngl-video iframe").attr("src", $("#mngl-video iframe").attr("src"));
	  });

});


// let nav = document.querySelector("header");
// window.onscroll = function () {
//     if(document.documentElement.scrollTop  > 20){
//         nav.classList.add("scroll-on");
//     }else{
//         nav.classList.remove("scroll-on");
//     }
// }

  