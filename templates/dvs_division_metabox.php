<table>
<tr valign="top">
<td class="metabox_label_column">
<label for="meta_a">Meta A</label>
</td>
<td>
<input type="text" id="meta_a" name="meta_a" value="<?php echo @get_post_meta($post->ID, 'meta_a', true); ?>" />
</td>
<tr>
<tr valign="top">
<td class="metabox_label_column">
<label for="meta_a">Meta B</label>
</td>
<td>
<input type="text" id="meta_b" name="meta_b" value="<?php echo @get_post_meta($post->ID, 'meta_b', true); ?>" />
</td>
<tr>
<tr valign="top">
<td class="metabox_label_column">
<label for="meta_a">Meta C</label>
</td>
<td>
<input type="text" id="meta_c" name="meta_c" value="<?php echo @get_post_meta($post->ID, 'meta_c', true); ?>" />
</td>
<tr>
</table>