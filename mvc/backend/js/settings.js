(function($) {
  'use strict';
  $(function() {
    $(".nav-settings").click(function() {
      $("#right-sidebar").toggleClass("open");
    });
    $(".settings-close").click(function() {
      $("#right-sidebar,#theme-settings").removeClass("open");
    });

    $("#settings-trigger").on("click", function() {
      $("#theme-settings").toggleClass("open");
    });


    //background constants
    var navbar_classes = "navbar-danger navbar-success navbar-warning navbar-dark navbar-light navbar-primary navbar-info navbar-pink";
    var sidebar_classes = "sidebar-light sidebar-dark";
    var $body = $("body");

    //sidebar backgrounds
    $("#sidebar-default-theme").on("click", function() {
      $body.removeClass(sidebar_classes);
      $(".sidebar-bg-options").removeClass("selected");
      $(this).addClass("selected");
    });
    $("#sidebar-dark-theme").on("click", function() {
      $body.removeClass(sidebar_classes);
      $body.addClass("sidebar-dark");
      $(".sidebar-bg-options").removeClass("selected");
      $(this).addClass("selected");
    });


    //Navbar Backgrounds
    $(".tiles.primary").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-primary");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.success").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-success");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.warning").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-warning");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.danger").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-danger");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.info").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-info");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.dark").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-dark");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });
    $(".tiles.light").on("click", function() {
      $(".navbar").removeClass(navbar_classes);
      $(".navbar").addClass("navbar-light");
      $(".tiles").removeClass("selected");
      $(this).addClass("selected");
    });

    // Menú: barra inferior horizontal o sidebar admin
    $('[data-toggle="horizontal-menu-toggle"]').on("click", function() {
      if ($("#admin-sidebar").length) {
        $("#admin-sidebar").toggleClass("admin-sidebar-open");
      } else if ($(".horizontal-menu .bottom-navbar").length) {
        $(".horizontal-menu .bottom-navbar").toggleClass("header-toggled");
      }
    });
    var $pageNav = $(".horizontal-menu .page-navigation > .nav-item");
    if ($pageNav.length) {
      $pageNav.on("click", function(event) {
        if (window.matchMedia("(max-width: 991px)").matches) {
          if (!$(this).hasClass("show-submenu")) {
            $pageNav.removeClass("show-submenu");
          }
          $(this).toggleClass("show-submenu");
        }
      });
    }

    $(document).on("click", "#admin-sidebar a.nav-link", function() {
      if ($(this).attr("data-toggle") === "collapse") {
        return;
      }
      if (window.matchMedia("(max-width: 991px)").matches) {
        $("#admin-sidebar").removeClass("admin-sidebar-open");
      }
    });

    $(window).scroll(function() {
      if(window.matchMedia('(min-width: 992px)').matches) {
        var header = $('.horizontal-menu');
        if ($(window).scrollTop() >= 71) {
          $(header).addClass('fixed-on-scroll');
        } else {
          $(header).removeClass('fixed-on-scroll');
        }
      }
    });

  });
})(jQuery);