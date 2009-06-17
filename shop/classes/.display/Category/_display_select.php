<?php
if ( $i == 0 )
{
	?>
	<select name="Category_id" id="category_select" >
		<option value="">All Categories</option>
		<option value="">---</option>
	<?php
}

?><option value="<?php echo to_string($this->id); ?>" <?php if ( $category_id == $this->id ) { ?>selected="selected"<?php } ?>><?php

		echo to_string($this->row['title']);
		
		if ( !empty($Category_type) )
		{
			$where = array();
			$where[] = array('category_id', $this->id);
			switch ( $Original_Category_type )
			{
				case 'purchases':
				case 'downloads':
					$where[] = array('display', true);
					break;
					
				case 'products':
					break;
			}
			switch ( $Original_Category_type )
			{
				case 'purchases':
					$where[] = array('release_level', 'purchase');
					break;
				case 'downloads':
					$where[] = array('release_level', 'purchase', '!=');
					break;
					
				case 'products':
					break;
			}
			echo ' (';
			$total = $this->DB->total(
				// TABLE
				$Category_type,
				// WHERE
				$where
			);
			var_export($total);
			echo ')';
		}
		
?></option><?php

if ( $i == $n-1 )
{
	?>
		<option value="">---</option>
		<option value="NULL"  <?php if ( $category_id == 'null' ) { ?>selected="selected"<?php } ?>>
			<?php
				echo $this->table['columns']['title']['null_title'];
				
				if ( !empty($Category_type) )
				{
					echo ' (';
					$total = $this->DB->total(
						// TABLE
						$Category_type,
						// WHERE
						array(
							// array( COLUMN, VALUE, BOOLEAN OPERATOR, LOGIC OPERATOR )
							array('category_id',	NULL)
						)
					);
					var_export($total);
					echo ')';
				}
			?>
		</option>
	</select>
	<input type="submit" name="submit"  value="Display" title="Display" />
	<?php
}
?>