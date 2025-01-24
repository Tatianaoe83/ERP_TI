<?php

namespace Database\Factories;

use App\Models\UnidadesDeNegocio;
use Illuminate\Database\Eloquent\Factories\Factory;

class UnidadesDeNegocioFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = UnidadesDeNegocio::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'NombreEscuela' => $this->faker->word,
        'RFC' => $this->faker->word,
        'Direccion' => $this->faker->word,
        'NumTelefono' => $this->faker->word,
        'created_at' => $this->faker->date('Y-m-d H:i:s'),
        'updated_at' => $this->faker->date('Y-m-d H:i:s')
        ];
    }
}
