<footer id="colophon" class="site-footer">
    <div class="site-info">
        <a href="<?php echo esc_url(__('https://wordpress.org/', 'THEME_TEXTDOMAIN')); ?>">
            <?php

            printf(esc_html__('Proudly powered by %s', 'THEME_TEXTDOMAIN'), 'WordPress');
            ?>
        </a>
        <span class="sep"> | </span>
        <?php
        /* translators: 1: Theme name, 2: Theme author. */
        printf(esc_html__('Theme: %1$s by %2$s.', 'THEME_TEXTDOMAIN'), 'THEME_TEXTDOMAIN', '<a href="https://automattic.com/">Automattic</a>');
        ?>
    </div><!-- .site-info -->
</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>