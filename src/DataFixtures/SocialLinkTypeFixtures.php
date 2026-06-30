<?php

namespace App\DataFixtures;

use App\Entity\SocialLinkType;
use App\Repository\SocialLinkTypeRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SocialLinkTypeFixtures extends Fixture
{
    public const TYPE_WEBSITE = 'website';
    public const TYPE_FACEBOOK = 'facebook';
    public const TYPE_INSTAGRAM = 'instagram';
    public const TYPE_X = 'x';
    public const TYPE_LINKEDIN = 'linkedin';

    /**
     * @var array<string, array{label: string, regex: string}>
     */
    public const TYPES = [
        self::TYPE_WEBSITE => [
            'label' => 'Site Web',
            'regex' => '/^https?:\/\/(?:[a-z0-9-]+\.)+[a-z]{2,}(?:[\/?#][^\s]*)?$/i',
        ],
        self::TYPE_FACEBOOK => [
            'label' => 'Page Facebook',
            'regex' => '/^https?:\/\/(?:www\.|m\.)?facebook\.com\/(?:profile\.php\?id=\d+|(?:pages\/)?[A-Za-z0-9._-]+(?:\/\d+)?)\/?(?:[?#][^\s]*)?$/i',
        ],
        self::TYPE_INSTAGRAM => [
            'label' => 'Compte Instagram',
            'regex' => '/^https?:\/\/(?:www\.)?instagram\.com\/[A-Za-z0-9._]{1,30}\/?(?:[?#][^\s]*)?$/i',
        ],
        self::TYPE_X => [
            'label' => 'Compte X',
            'regex' => '/^https?:\/\/(?:www\.)?(?:x\.com|twitter\.com)\/[A-Za-z0-9_]{1,15}\/?(?:[?#][^\s]*)?$/i',
        ],
        self::TYPE_LINKEDIN => [
            'label' => 'Page LinkedIn',
            'regex' => '/^https?:\/\/(?:www\.)?linkedin\.com\/(?:company|showcase)\/[A-Za-z0-9_.-]+\/?(?:[?#][^\s]*)?$/i',
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        /** @var SocialLinkTypeRepository $repository */
        $repository = $manager->getRepository(SocialLinkType::class);

        foreach (self::TYPES as $code => $config) {
            $type = $repository->findOneBy(['code' => $code]);
            if (!$type instanceof SocialLinkType) {
                $type = new SocialLinkType();
                $type->setCode($code);
                $manager->persist($type);
            }

            $type->setLabel($config['label']);
            $type->setValidationRegex($config['regex']);
        }

        $manager->flush();
    }
}
