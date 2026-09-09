<style>
    .debug-helper {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
    }
    .debug-helper__button {
        display: block;
        margin: 0;
        padding: 10px 25px;
        width: 100%;
        font-weight: 600;
        text-align: left;
        background-color: #aaa;
        border: 0;
        border-top: 1px solid #666;
        border-bottom: 1px solid #666;
    }

    .debug-helper__body {
        background-color: #ddd;
        transition: all .3s ease;
    }
    
    .debug-helper__body.hide {
        height: 0 !important;
        overflow: hidden;
    }

    .debug-helper__inner {
        padding: 30px;
        max-height: 400px;
        overflow: auto;
    }

    .debug-helper__inner pre {
        padding: 15px;
        min-width: 100%;
        width: fit-content;
        background-color: #ccc;
        border: 1px solid #bbb;
        overflow: visible;
    }
</style>

<?php
    $debugBacktrace = debug_backtrace();
    $origin         = '';

    foreach ( $debugBacktrace as $trace ) {
        if ( ( $trace['function'] ?? '' ) === 'drg_show_debug_helper' ) {
            $origin = $trace['file'] ?? '';
        }
    }
?>


<div class="debug-helper">
    <div class="debug-helper__header">
        <button class="debug-helper__button">
            Debug Helper - <?php echo esc_html($origin); ?>
        </button>
    </div>
    <div class="debug-helper__body">
        <div class="debug-helper__inner">
            <pre>
                <?php /* Input your content here */ ?>
                <?php print_r( debug_backtrace() ); ?>
            </pre>
        </div>
    </div>
</div>

<script>
    document.querySelector('.debug-helper__body').style.height = document.querySelector('.debug-helper__body').offsetHeight + 'px';
    document.querySelector('.debug-helper__button').addEventListener('click', function() {
        document.querySelector('.debug-helper__body').classList.toggle('hide');
    }, false);
</script>