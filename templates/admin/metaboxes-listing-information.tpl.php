<ul id="wpbdp-listing-metabox-tab-selector" class="wpbdp-admin-tab-nav subsubsub">
    <?php foreach ( $tabs as $tab ): ?>
    <li><a href="#wpbdp-listing-metabox-<?php echo $tab['id']; ?>"><?php echo $tab['label']; ?></a></li>
    <?php endforeach; ?>
</ul>

<?php foreach ( $tabs as $tab ): ?>
    <?php echo $tab['content']; ?>
<?php endforeach; ?>
