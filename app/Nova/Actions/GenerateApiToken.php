<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class GenerateApiToken extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Generate API Token';

    /**
     * Perform the action on the given models.
     *
     * @param  \Laravel\Nova\Fields\ActionFields  $fields
     * @param  \Illuminate\Support\Collection  $models
     * @return mixed
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        // A plain-text token is only shown once, so restrict to a single user.
        if ($models->count() !== 1) {
            return Action::danger('Generate a token for one user at a time so the plain-text value can be shown.');
        }

        $abilities = collect(explode(',', (string) $fields->abilities))
            ->map(fn ($ability) => trim($ability))
            ->filter()
            ->values()
            ->all();

        $token = $models->first()->createToken(
            $fields->name ?: 'api-token',
            empty($abilities) ? ['*'] : $abilities
        );

        return Action::message('API token (copy it now — it will not be shown again): '.$token->plainTextToken);
    }

    /**
     * Get the fields available on the action.
     *
     * @param  \Laravel\Nova\Http\Requests\NovaRequest  $request
     * @return array
     */
    public function fields(NovaRequest $request)
    {
        return [
            Text::make('Name')
                ->rules('required', 'max:255')
                ->help('A label to identify this token.'),

            Text::make('Abilities')
                ->help('Comma-separated abilities. Leave blank to grant all abilities (*).'),
        ];
    }
}
