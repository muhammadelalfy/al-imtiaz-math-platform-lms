enum UserRole {
  admin,
  teacher,
  parent,
  student;

  String get apiValue => name;

  String get label => switch (this) {
    UserRole.admin => 'مسؤول المؤسسة',
    UserRole.teacher => 'المعلم',
    UserRole.parent => 'ولي الأمر',
    UserRole.student => 'الطالب',
  };

  static UserRole fromApi(String value) => UserRole.values.firstWhere(
    (UserRole role) => role.apiValue == value,
    orElse: () => UserRole.student,
  );
}

class AppUser {
  const AppUser({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.isSuperAdmin,
    this.academyName,
  });

  factory AppUser.fromJson(Map<String, Object?> json) {
    return AppUser(
      id: json['id'] as int,
      name: json['name'] as String,
      email: json['email'] as String,
      role: UserRole.fromApi(json['role'] as String),
      isSuperAdmin: json['is_super_admin'] as bool? ?? false,
      academyName: json['academy_name'] as String?,
    );
  }

  final int id;
  final String name;
  final String email;
  final UserRole role;
  final bool isSuperAdmin;
  final String? academyName;
}

class AuthSession {
  const AuthSession({required this.user, required this.token});

  final AppUser user;
  final String token;
}
