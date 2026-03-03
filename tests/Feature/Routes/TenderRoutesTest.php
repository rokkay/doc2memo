<?php

use Symfony\Component\Routing\Exception\RouteNotFoundException;

it('exposes only the expected tender route names for web flow', function (): void {
    expect(route('tenders.index'))->toBe(url('/tenders'));
    expect(route('tenders.create'))->toBe(url('/tenders/create'));
    expect(route('tenders.store'))->toBe(url('/tenders'));
    expect(route('tenders.show', 1))->toBe(url('/tenders/1'));
    expect(route('tenders.analyze', 1))->toBe(url('/tenders/1/analyze'));
    expect(route('tenders.generate-memory', 1))->toBe(url('/tenders/1/generate-memory'));
});

it('does not expose unimplemented tender routes', function (): void {
    expect(fn (): string => route('tenders.edit', 1))->toThrow(RouteNotFoundException::class);
    expect(fn (): string => route('tenders.update', 1))->toThrow(RouteNotFoundException::class);
    expect(fn (): string => route('tenders.destroy', 1))->toThrow(RouteNotFoundException::class);
});
