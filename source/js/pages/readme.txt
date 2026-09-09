Place to put specific page javascript.

Pattern (2 files per page max: layout.js + this page file):
- layout.js imports GSAP/Bootstrap once and publishes them on window.DRG.
- A page file reads the shared instance, null-safe:
      const { gsap, ScrollTrigger, fn } = window.DRG || {};
- Never import gsap inside a page file (creates a separate instance).

To ship a new page file you must do BOTH:
1. Build:   add a line in webpack.mix.js
            mix.js('source/js/pages/<name>.js', 'assets/js/page/')
2. Enqueue: in inc/functions-style-script.php, inside the matching
            page condition, depend on layoutJS so window.DRG exists first:
            drg_print_js('<name>Js', 'page/<name>.js', array('layoutJS'));

See docs/ARCHITECTURE.md for the full model.
