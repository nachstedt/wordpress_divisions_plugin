<table>
<?php foreach ($locations as $name => $description) {?>
	<tr valign="top">
		<td>
			<input type="checkbox"
				id="nav_menu_location_<?php echo $name;?>"
				name="replaced_nav_menus[]"
				value="<?php echo $name;?>"
				<?php if (in_array($name, $replaced_nav_menus)) echo "checked"?>
			/>
		</td>
		<td class="metabox_label_column">
			<label for="nav_menu_location_<?php echo $name;?>"><?php echo $description?></label>
		</td>
	</tr>
<?php }?>
</table>