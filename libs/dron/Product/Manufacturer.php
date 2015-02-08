<?php

namespace Bitcont\Dron\Product;

use Doctrine\ORM\Mapping as Doctrine,
	Doctrine\Common\Persistence\PersistentObject,
	Doctrine\ODM\MongoDB\DocumentManager,
	Doctrine\Common\Collections\ArrayCollection,
	Nette\Security\IResource,
	//Siesta\Doctrine\TimestampableTrait,
	//Siesta\Security\IResourceTrait,
	Siesta\Pricing\Rate,
	Siesta\Pricing\RoomRatePeriod,
	Siesta\Localization\Language\Language,
	DateTime;


/**
 * @Doctrine\Entity
 * @Doctrine\Table(name = "room")
 */
class Manufacturer extends PersistentObject
{

// 	/**
// 	 * IResource implementation.
// 	 */
// 	use IResourceTrait;
// 	
// 	/**
// 	 * Creates timestamps for CRUD operations.
// 	 */
// 	use TimestampableTrait;
	
	
	/**
	 * Id.
	 * 
	 * @Doctrine\Id
	 * @Doctrine\Column(type = "integer")
	 * @Doctrine\GeneratedValue
	 * @var int
	 */
	protected $id;
	
	/**
	 * Name.
	 * 
	 * @Doctrine\Column
	 * @var string
	 */
	protected $name;
	
	/**
	 * Maximum occuppancy.
	 * 
	 * @Doctrine\Column(type = "integer", name = "number_of_adults")
	 * @var int
	 */
	protected $numberOfAdults;
	
	/**
	 * Room id in partner's API.
	 * 
	 * @Doctrine\Column(type = "integer", nullable = true, name = "remote_id")
	 * @var int
	 */
	protected $remoteId;
	
	/**
	 * Owning property.
	 * 
	 * @Doctrine\ManyToOne(targetEntity = "Property", inversedBy = "rooms")
	 * @var Property
	 */
	protected $property;
	
	/**
	 * Translations.
	 * 
	 * @Doctrine\OneToMany(targetEntity = "Siesta\Localization\Language\RoomTranslation", mappedBy = "room", cascade = {"all"})
	 * @var ArrayCollection of Siesta\Localization\Language\RoomTranslation
	 */
	protected $translations;
	
	/**
	 * Rates this room can use.
	 * 
	 * @Doctrine\ManyToMany(targetEntity = "Siesta\Pricing\Rate", mappedBy = "rooms") 	 	 	 
	 * @var ArrayCollection of Rate
	 */
	protected $rates;
	
	
	/**
	 * @param Property $property
	 */
	public function __construct(Property $property)
	{
		$property->addRooms($this);
		$this->translations = new ArrayCollection;
		$this->rates = new ArrayCollection;
		$this->createdAt = new DateTime; // required by TimestampableTrait
	}
	
	
	/**
	 * Returns translation for given language or null if it does not exist.
	 * 
	 * @param Siesta\Localization\Language\Language $language
	 * @return Siesta\Localization\Language\RoomTranslation|NULL
	 */
	public function getTranslation(Language $language)
	{
		foreach ($this->getTranslations() as $translation) {
			if ($translation->getLanguage() === $language) return $translation;
		}
	}
	
	
	/**
	 * Returns room rate period for this room.
	 * 
	 * @param Doctrine\ODM\MongoDB\DocumentManager $documentManager
	 * @param Siesta\Pricing\Rate $rate
	 * @param DateTime $firstNight
	 * @param DateTime $lastNight
	 * @return RoomRatePeriod
	 */
	public function getRoomRatePeriod(DocumentManager $documentManager, Rate $rate, DateTime $firstNight, DateTime $lastNight)
	{
		return new RoomRatePeriod($documentManager, $this, $rate, $firstNight, $lastNight);
	}
	
	
	/**
	 * Returns the best offer for given period. If period not given, searches
	 * for the best offer within next 3 months.
	 * 
	 * @param Doctrine\ODM\MongoDB\DocumentManager $documentManager
	 * @param DateTime $firstNight
	 * @param DateTime $lastNight
	 * @param int $numberOfRooms
	 * @return RoomRateOffer|NULL
	 */
	public function getCheapestAvailableOffer(DocumentManager $documentManager, DateTime $firstNight = NULL, DateTime $lastNight = NULL, $numberOfRooms = 1)
	{
		$offers = array();
		
		foreach ($this->getProperty()->getRates() as $rate) {
			$offer = NULL;
			
			if (!$firstNight || !$lastNight) { // unknown dates
				$period = $this->getRoomRatePeriod($documentManager, $rate, new DateTime, new DateTime('+1 month'));
				$offer = $period->getCheapestAvailableOffer();
			
			} else { // known dates
				$period = $this->getRoomRatePeriod($documentManager, $rate, $firstNight, $lastNight);
				$offer = $period->getAvailableOffer($numberOfRooms);
			}
			
			if ($offer) $offers[] = $offer;
		}
		
		// sort from the cheapest
		usort($offers, function($a, $b) {
			
			// non-discount rate first
			if ($a->getUnitPrice()->val() === $b->getUnitPrice()->val()) {
				return ((int) $a->getRoomRatePeriod()->getRate()->getIsDiscount()) - ((int) $a->getRoomRatePeriod()->getRate()->getIsDiscount());
			}
			return $a->getUnitPrice()->val() - $b->getUnitPrice()->val(); 
		});
		
		return reset($offers);
	}
	
	
	
	
	/***************************************************************************
	**************************** PHP 5.3 TRAITS ********************************
	***************************************************************************/
	
	
	/**
	 * IResource implementation.
	 * Returns namespace-trimmed classname.
	 * 
	 * @return string
	 */
	public function getResourceId()
	{
		return join('', array_slice(explode('\\', get_class($this)), -1));
	}
	
	
	/**
	 * Creation timestamp.
	 *	 	
	 * @Doctrine\Column(type = "datetime", name = "created_at")
	 * @var DateTime
	 */
	protected $createdAt;
	
	/**
	 * Removal timestamp.
	 *	 	
	 * @Doctrine\Column(type = "datetime", nullable = true, name = "removed_at")
	 * @var DateTime
	 */
	protected $removedAt;
	
	
	/**
	 * Returns TRUE if entity is removed.
	 * 
	 * @return bool
	 */
	public function isRemoved()
	{
		return (bool) $this->removedAt;
	}
}


