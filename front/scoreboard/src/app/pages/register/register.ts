// src/app/pages/register/register.ts
import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule, NgForm } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { AuthenticationService } from '../../core/services/authentication.service';
import { RoleDto } from '../../core/models/login-response.dto';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink],
  templateUrl: './register.html',
  styleUrls: ['./register.css']
})
export class RegisterComponent implements OnInit {
  username = '';
  password = '';
  roleId: number | null = null;

  roles: RoleDto[] = [];
  isLoadingRoles = false;
  isRegistering = false;
  errorMessage = '';
  successMessage = '';

  constructor(
    private authService: AuthenticationService
  ) {}

  ngOnInit(): void {
    this.loadRoles();
  }

  submit(form: NgForm) {
    const username = this.username.trim();
    const password = this.password.trim();

    if (!username || !password) {
      this.errorMessage = 'Usuario y contraseña son obligatorios.';
      return;
    }

    if (!this.roleId) {
      this.errorMessage = 'Selecciona un rol válido.';
      return;
    }

    this.errorMessage = '';
    this.successMessage = '';
    this.isRegistering = true;

    this.authService.register(username, password, this.roleId).subscribe({
      next: response => {
        this.successMessage = response?.message || 'Usuario registrado correctamente.';
        form.resetForm();
        this.username = '';
        this.password = '';
        this.roleId = null;
      },
      error: err => {
        const message = err?.error?.message ?? err?.error ?? 'No se pudo completar el registro.';
        this.errorMessage = typeof message === 'string' ? message : 'No se pudo completar el registro.';
      },
      complete: () => {
        this.isRegistering = false;
      }
    });
  }

  private loadRoles() {
    this.isLoadingRoles = true;
    this.roles = [];

    this.authService.getRoles().subscribe({
      next: roles => {
        this.roles = roles ?? [];
        if (!this.roles.length) {
          this.errorMessage = 'No hay roles disponibles para registrarse.';
        } else {
          this.errorMessage = '';
        }
      },
      error: () => {
        this.errorMessage = 'No se pudieron cargar los roles disponibles.';
      },
      complete: () => {
        this.isLoadingRoles = false;
      }
    });
  }
}
