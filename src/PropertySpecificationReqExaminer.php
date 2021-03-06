<?php

namespace SMW;

use SMW\SemanticData;
use SMW\Store;
use SMW\DIProperty;
use SMW\DataItemFactory;
use SMWDataItem as DataItem;
use SMW\PropertyAnnotators\MandatoryTypePropertyAnnotator;
use SMW\Protection\EditProtectionValidator;

/**
 * Examines codified requirements for listed types of property specifications which
 * in case of a violation returns a message with the details of that violation.
 *
 * @license GNU GPL v2+
 * @since 2.5
 *
 * @author mwjames
 */
class PropertySpecificationReqExaminer {

	/**
	 * @var Store
	 */
	private $store;

	/**
	 * @var EditProtectionValidator
	 */
	private $editProtectionValidator;

	/**
	 * @var SemanticData
	 */
	private $semanticData;

	/**
	 * @var DataItemFactory
	 */
	private $dataItemFactory;

	/**
	 * @var boolean
	 */
	private $editProtectionRight = false;

	/**
	 * @var boolean
	 */
	private $reqLock = false;

	/**
	 * @since 2.5
	 *
	 * @param Store $store
	 * @param EditProtectionValidator $editProtectionValidator
	 */
	public function __construct( Store $store, EditProtectionValidator $editProtectionValidator ) {
		$this->store = $store;
		$this->editProtectionValidator = $editProtectionValidator;
	}

	/**
	 * @since 2.5
	 *
	 * @param SemanticData|null $semanticData
	 */
	public function setSemanticData( SemanticData $semanticData = null ) {
		$this->semanticData = $semanticData;
	}

	/**
	 * @since 2.5
	 *
	 * @param string|boolean $editProtectionRight
	 */
	public function setEditProtectionRight( $editProtectionRight ) {
		$this->editProtectionRight = $editProtectionRight;
	}

	/**
	 * Whether a specific property requires a lock nor not.
	 *
	 * @since 3.0
	 *
	 * @param boolean
	 */
	public function reqLock() {
		return $this->reqLock;
	}

	/**
	 * @since 2.5
	 *
	 * @param DIProperty $property
	 *
	 * @return array|null
	 */
	public function checkOn( DIProperty $property ) {

		$subject = $property->getCanonicalDiWikiPage();
		$title = $subject->getTitle();

		$semanticData = $this->store->getSemanticData( $subject );

		if ( $this->semanticData === null ) {
			$this->semanticData = $semanticData;
		}

		$this->reqLock = false;
		$this->dataItemFactory = new DataItemFactory();

		if ( $semanticData->hasProperty( new DIProperty( DIProperty::TYPE_CHANGE_PROP ) ) ) {
			$this->reqLock = true;
			return array(
				'error',
				'smw-property-req-violation-change-propagation-locked',
				$property->getLabel()
			);
		}

		if ( $this->reqLock === false && $this->editProtectionRight && $this->editProtectionValidator->hasEditProtection( $title ) ) {
			return array(
				$property->isUserDefined() ? 'error' : 'warning',
				'smw-edit-protection',
				$property->getLabel(),
				$this->editProtectionRight
			);
		}

		if ( !$property->isUserDefined() ) {
			return $this->checkOnTypeForPredefinedProperty( $property );
		}

		$type = $property->findPropertyTypeID();

		if ( $type === '_ref_rec' || $type === '_rec' ) {
			return $this->checkOnFieldList( $property );
		}

		if ( $type === '_eid' ) {
			return $this->checkOnExternalFormatterUri( $property );
		}

		if ( $this->semanticData->getOption( MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE ) ) {
			return $this->checkOnImportedVocabType( $property );
		}
	}

	/**
	 * A violation occurs when a predefined property contains a `Has type` annotation
	 * that is incompatible with the default type.
	 */
	private function checkOnTypeForPredefinedProperty( $property ) {

		if ( $property->getKey() === '_EDIP' ) {
			return $this->checkOnEditProtectionRight( $property );
		}

		if ( !$this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_TYPE' ) ) ) {
			return;
		}

		$typeValues = $this->semanticData->getPropertyValues(
			$this->dataItemFactory->newDIProperty( '_TYPE' )
		);

		if ( $typeValues !== array() ) {
			list( $url, $type ) = explode( "#", end( $typeValues )->getSerialization() );
		}

		if ( DataTypeRegistry::getInstance()->isEqualByType( $type, $property->findPropertyTypeID() ) ) {
			return;
		}

		$prop = $this->dataItemFactory->newDIProperty( $type );

		return array(
			'error',
			'smw-property-req-violation-predefined-type',
			$property->getCanonicalLabel(),
			$prop->getCanonicalLabel()
		);
	}

	/**
	 * Examines whether the setting `smwgEditProtectionRight` contains an appropriate
	 * value or is disabled in order for the `Is edit protected` property to function.
	 */
	private function checkOnEditProtectionRight( $property ) {

		if ( $this->editProtectionRight !== false ) {
			return;
		}

		return array(
			'warning',
			'smw-edit-protection-disabled',
			$property->getCanonicalLabel()
		);
	}

	/**
	 * A violation occurs when a Reference or Record typed property does not denote
	 * a `Has fields` declaration.
	 */
	private function checkOnFieldList( $property ) {

		if ( $this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_LIST' ) ) ) {
			return;
		}

		$prop = $this->dataItemFactory->newDIProperty( $property->findPropertyTypeID() );

		return array(
			'error',
			'smw-property-req-violation-missing-fields',
			$property->getLabel(),
			$prop->getCanonicalLabel()
		);
	}

	/**
	 * A violation occurs when the External Identifier typed property does not declare
	 * a `External formatter URI` declaration.
	 */
	private function checkOnExternalFormatterUri( $property ) {

		if ( $this->semanticData->hasProperty( $this->dataItemFactory->newDIProperty( '_PEFU' ) ) ) {
			return;
		}

		return array(
			'error',
			'smw-property-req-violation-missing-formatter-uri',
			$property->getLabel()
		);
	}

	/**
	 * A violation occurs when the `Imported from` property detects an incompatible
	 * `Has type` declaration.
	 */
	private function checkOnImportedVocabType( $property ) {

		$typeValues = $this->semanticData->getPropertyValues(
			$this->dataItemFactory->newDIProperty( '_TYPE' )
		);

		$dataItem = $this->semanticData->getOption(
			MandatoryTypePropertyAnnotator::IMPO_REMOVED_TYPE
		);

		if ( $dataItem instanceof DataItem && end( $typeValues )->equals( $dataItem ) ) {
			return;
		}

		return array(
			'warning',
			'smw-property-req-violation-import-type',
			$property->getLabel()
		);
	}

}
