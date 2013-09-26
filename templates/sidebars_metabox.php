<table>
<?php foreach ($sidebars as $sidebar) {?>
	<tr valign="top">
		<td>
			<input type="checkbox"
				id="sidebar_<?php echo $sidebar['id'];?>"
				name="replaced_sidebars[]"
				value="<?php echo $sidebar['id'];?>"
				<?php if (in_array($sidebar['id'], $replaced_sidebars)) echo "checked"?>
			/>
		</td>
		<td class="metabox_label_column">
			<label for="sidebar_<?php echo $sidebar['id'];?>">
				<?php echo $sidebar['name']?>
			</label>
		</td>
	</tr>
<?php }?>
</table>