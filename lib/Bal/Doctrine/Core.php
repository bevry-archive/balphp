<?php

/** Container of Generic Doctrine Functions */
abstract class Bal_Doctrine_Core {
	
	/**
	 * Ensure a One Relation that has a cache
	 * @return bool
	 */
	public static function ensureOne ( $Event, $relation ) {
		# Prepare
		$save = false;
		
		# Fetch
		$Invoker = $Event->getInvoker();
		
		# Prepare Arguments
		$down = strtolower($relation);
		$up = $relation;
		
		# Result
		$result = isset($Invoker->$up) ? $Invoker->$up->title : null;
		
		# Ensure
		if ( $Invoker->get($down) != $result ) {
			$Invoker->set($down, $result, false);
			$save = true;
		}
		
		# Done
		return $save;
	}
	
	/**
	 * Ensure a Many Relation that has a cache
	 * @return bool
	 */
	public static function ensureMany ( $Event, $relation ) {
		# Prepare
		$save = false;
		$result = array();
		
		# Fetch
		$Invoker = $Event->getInvoker();
		
		# Prepare Arguments
		$down = strtolower($relation);
		$up = $relation;
		
		# Result
		foreach ( $Invoker->$up as $Item ) {
			$result[] = $Item->title;
		}
		$result = implode(', ', $result);
		
		# Ensure
		if ( $Invoker->get($down) != $result ) {
			$Invoker->set($down, $result, false);
			$save = true;
		}
		
		# Done
		return $save;
	}
	
	/**
	 * Ensure Tags
	 * @param Doctrine_Event $Event
	 * @return bool
	 */
	public static function ensureTags ( $Event, $tagRelation, $tagField ) {
		# Prepare
		$save = false;
		
		# Fetch
		$Invoker = $Event->getInvoker();
		$modified = $Invoker->getModified();
		$modifiedLast = $Invoker->getLastModified();
		$tagRelationNames = $tagRelation.'Names';
		
		# Fetch
		$tagsSystemOrig = $Invoker->$tagRelationNames;
		$tagsUserOrig = $Invoker->$tagField;
		$tagsSystem = prepare_csv_str($tagsSystemOrig);
		$tagsUser = prepare_csv_str($tagsUserOrig);
		$tagsUserNewer = array_key_exists($tagField, $modified);
		$tagsSystemNewer = !array_key_exists($tagField, $modified) && !array_key_exists($tagField, $modifiedLast);
		$tagsDiffer = $tagsUser != $tagsSystem;
		
		# TagField > TagField
		if ( ($tagsDiffer || $tagsUserOrig != $tagsUser) && $tagsUserNewer ) {
			# TagField is newer than TagField
			
			# Save TagField
			$Invoker->set($tagField, $tagsUser, false); // false at end to prevent comparison
			
			# Save
			$save = true;
		}
		
		# TagField > TagRelation
		if ( $tagsDiffer && !$tagsSystemNewer ) {
			# TagField is newer than TagRelation
			
			# Check whether we can save
			if ( $Invoker->id ) {
				# Save TagRelation
				$Invoker->$tagRelation = $tagsUser;
				
				# Save
				$save = true;
			}
		}
		
		# TagRelation > $TagField
		if ( $tagsDiffer && $tagsSystemNewer ) {
			# TagRelation is newer than TagField
			
			# Save TagField
			$Invoker->set($tagField, $tagsSystem, false); // false at end to prevent comparison
			
			# Save
			$save = true;
		}
		
		# Return
		return $save;
	}
	
	
	/**
	 * Ensure Consistency
	 * @param Doctrine_Event $Event
	 * @return boolean	wheter or not to save
	 */
	public static function ensure ( $Event, $Event_type, array $checks ){
		# Prepare
		$ensure = array();
		$Invoker = $Event->getInvoker();
		
		# Ensure
		foreach ( $checks as $check ) {
			$ensure[] = $Invoker->$check($Event,$Event_type);
		}
		
		# Result
		$result = in_array(true,$ensure);
		
		# Return result
		return $result;
	}
	
}