<?php

namespace App\Service;

use App\Entity\Laundromat;
use App\Entity\LaundromatClosure;
use App\Entity\LaundromatExceptionalClosure;
use App\Entity\LaundromatExceptionalClosureSlot;
use App\Entity\LaundromatMedia;
use App\Entity\Service;

trait LaundromatArrayBuilder
{
    /**
     * @return list<array{day: string|null, startTime: string|null, endTime: string|null}>
     */
    protected function buildOpeningHours(Laundromat $laundromat): array
    {
        $openingHours = [];
        foreach ($laundromat->getClosures() ?? [] as $closure) {
            $openingHours[] = $this->closureToOpeningHour($closure);
        }

        return $openingHours;
    }

    /**
     * @return list<array{day: string|null, startTime: string|null, endTime: string|null}>
     */
    protected function buildOpeningHoursWithIds(Laundromat $laundromat): array
    {
        $openingHours = [];
        foreach ($laundromat->getClosures() ?? [] as $closure) {
            $openingHours[] = [
                'id' => $closure->getId(),
                'day' => $closure->getDay()?->value,
                'startTime' => $closure->getStartTime()?->format('H:i'),
                'endTime' => $closure->getEndTime()?->format('H:i'),
            ];
        }

        return $openingHours;
    }

    /**
     * @return list<string>
     */
    protected function buildServiceNames(Laundromat $laundromat): array
    {
        $services = [];
        foreach ($laundromat->getServices() ?? [] as $service) {
            if ($service instanceof Service) {
                $services[] = $service->getName();
            }
        }

        return $services;
    }

    /**
     * @return list<string>
     */
    protected function buildPaymentMethodNames(Laundromat $laundromat): array
    {
        $paymentMethods = [];
        foreach ($laundromat->getPaymentMethods() ?? [] as $paymentMethod) {
            $paymentMethods[] = $paymentMethod->getName();
        }

        return $paymentMethods;
    }

    /**
     * @return array{day: string|null, startTime: string|null, endTime: string|null}
     */
    private function closureToOpeningHour(LaundromatClosure $closure): array
    {
        return [
            'day' => $closure->getDay()?->value,
            'startTime' => $closure->getStartTime()?->format('H:i'),
            'endTime' => $closure->getEndTime()?->format('H:i'),
        ];
    }

    /**
     * @return list<array{day: string|null, startTime: string|null, endTime: string|null}>
     */
    protected function buildSlotOpeningHours(LaundromatExceptionalClosure $closure): array
    {
        $openingHours = [];
        foreach ($closure->getOpeningHours() as $slot) {
            if (!$slot instanceof LaundromatExceptionalClosureSlot) {
                continue;
            }
            $openingHours[] = [
                'day' => $slot->getDay()?->value,
                'startTime' => $slot->getStartTime()?->format('H:i'),
                'endTime' => $slot->getEndTime()?->format('H:i'),
            ];
        }

        return $openingHours;
    }

    /**
     * @return list<array{id: int|null, day: string|null, startTime: string|null, endTime: string|null}>
     */
    protected function buildSlotOpeningHoursWithIds(LaundromatExceptionalClosure $closure): array
    {
        $openingHours = [];
        foreach ($closure->getOpeningHours() as $slot) {
            if (!$slot instanceof LaundromatExceptionalClosureSlot) {
                continue;
            }
            $openingHours[] = [
                'id' => $slot->getId(),
                'day' => $slot->getDay()?->value,
                'startTime' => $slot->getStartTime()?->format('H:i'),
                'endTime' => $slot->getEndTime()?->format('H:i'),
            ];
        }

        return $openingHours;
    }

    /**
     * @return list<array{id: int|null, url: string|null, name: string|null, description: string|null}>
     */
    protected function buildPhotos(Laundromat $laundromat): array
    {
        $photos = [];
        foreach ($laundromat->getMedias() ?? [] as $mediaRelation) {
            if (!$mediaRelation instanceof LaundromatMedia) {
                continue;
            }
            $media = $mediaRelation->getMedia();
            $photos[] = [
                'id' => $mediaRelation->getId(),
                'url' => $media?->getLocation(),
                'name' => $media?->getOriginalName(),
                'description' => $mediaRelation->getDescription(),
            ];
        }

        return $photos;
    }
}
