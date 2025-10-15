// src/app/pages/login/login.ts
import { Component, OnInit } from '@angular/core';
import { FormsModule, NgForm } from '@angular/forms';
import { Router, ActivatedRoute, RouterLink } from '@angular/router';
import { AuthenticationService } from '../../core/services/authentication.service';
import { CommonModule } from '@angular/common';
import { LoginResponseDto, RoleDto } from '../../core/models/login-response.dto';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule, RouterLink],
  templateUrl: './login.html',
  styleUrls: ['./login.css']
})
export class LoginComponent implements OnInit {
  username = '';
  password = '';
  errorMessage = '';

  registerUsername = '';
  registerPassword = '';
  registerRoleId: number | null = null;
  registerError = '';
  registerSuccess = '';
  isRegistering = false;
  isLoadingRoles = false;
  roles: RoleDto[] = [];

  constructor(
    private authService: AuthenticationService,
    private router: Router,
    private route: ActivatedRoute
  ) {}

  ngOnInit(): void {
    this.loadRoles();
  }

  onSubmit() {
    const username = this.username.trim();
    const password = this.password.trim();
    if (!username || !password) {
      this.errorMessage = 'Ingresa un usuario y contraseña válidos.';
      return;
    }

    this.errorMessage = '';
    this.authService.login(username, password).subscribe({
      next: (res: LoginResponseDto) => {
        this.authService.saveUser(res);
        const returnUrl = this.route.snapshot.queryParamMap.get('returnUrl');
        if (returnUrl) {
          this.router.navigateByUrl(returnUrl);
          return;
        }
        const role = res.role?.name?.toLowerCase();
        this.router.navigate([role === 'admin' ? '/admin' : '/score/1']);
      },
      error: () => { this.errorMessage = 'Usuario o contraseña incorrectos'; }
    });
  }

  onRegister(form: NgForm) {
    const username = this.registerUsername.trim();
    const password = this.registerPassword.trim();

    if (!username || !password) {
      this.registerError = 'Usuario y contraseña son obligatorios.';
      return;
    }

    if (!this.registerRoleId) {
      this.registerError = 'Selecciona un rol válido.';
      return;
    }

    this.registerError = '';
    this.registerSuccess = '';
    this.isRegistering = true;

    this.authService.register(username, password, this.registerRoleId).subscribe({
      next: response => {
        this.registerSuccess = response?.message || 'Usuario registrado correctamente.';
        form.resetForm();
        this.registerUsername = '';
        this.registerPassword = '';
        this.registerRoleId = null;
      },
      error: err => {
        const message = err?.error?.message ?? err?.error ?? 'No se pudo completar el registro.';
        this.registerError = typeof message === 'string' ? message : 'No se pudo completar el registro.';
        this.isRegistering = false;
      },
      complete: () => {
        this.isRegistering = false;
      }
    });
  }

  private loadRoles() {
    this.isLoadingRoles = true;
    this.authService.getRoles().subscribe({
      next: roles => {
        this.roles = roles ?? [];
        if (this.roles.length) {
          this.registerError = '';
        }
      },
      error: () => {
        this.registerError = 'No se pudieron cargar los roles disponibles.';
      },
      complete: () => {
        this.isLoadingRoles = false;
      }
    });
  }
}
