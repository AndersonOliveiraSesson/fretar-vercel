<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => [
                'required',
                'email',
                'max:255',
                // o e-mail nao pode existir em NENHUMA das duas tabelas (veja RegisterUserRequest)
                Rule::unique('drivers', 'email'),
                Rule::unique('users', 'email'),
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cnh' => [
                'required',
                'string',
                'max:30',
                Rule::unique('drivers', 'cnh'),
            ],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'pix_key_type' => ['nullable', 'string', Rule::in(['cpf', 'email', 'phone', 'random']), 'required_with:pix_key'],
            'pix_key' => ['nullable', 'string', 'max:140', 'required_with:pix_key_type'],
            'vehicle_type' => ['nullable', 'string', 'max:50'],
            'license_plate' => ['nullable', 'string', 'max:20', Rule::unique('vehicles', 'license_plate')],
            'capacity_load_kg' => ['nullable', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Informe a senha.',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'cnh.required' => 'Informe a CNH.',
            'cnh.unique' => 'Esta CNH já está cadastrada.',
            'license_plate.unique' => 'Esta placa já está cadastrada.',
            'capacity_load_kg.numeric' => 'A capacidade de carga deve ser um número.',
            'capacity_load_kg.min' => 'A capacidade de carga deve ser maior que zero.',
        ];
    }

    public function withValidator($validator): void
    {
        // Veiculo e opcional no cadastro, mas se informado exige placa, tipo e capacidade.
        $veiculoInformado = fn ($input) => filled($input->vehicle_type) || filled($input->license_plate);
        $validator->sometimes('license_plate', 'required', $veiculoInformado);
        $validator->sometimes('vehicle_type', 'required', $veiculoInformado);
        $validator->sometimes('capacity_load_kg', 'required', $veiculoInformado);
    }
}