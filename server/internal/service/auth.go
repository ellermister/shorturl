package service

import (
	"errors"
	"strings"
	"time"
	"unicode/utf8"

	"github.com/chauncey/shorturl/server/internal/config"
	"github.com/chauncey/shorturl/server/internal/model"
	"github.com/golang-jwt/jwt/v5"
	"golang.org/x/crypto/bcrypt"
	"gorm.io/gorm"
)

var (
	ErrAuthFailed      = errors.New("invalid username or password")
	ErrUserExists      = errors.New("username already exists")
	ErrUserDisabled    = errors.New("user disabled")
	ErrWeakPassword    = errors.New("password too short")
	ErrInvalidUsername = errors.New("invalid username")
)

type AuthService struct {
	db    *gorm.DB
	cfg   config.Config
	plans *PlanService
}

func NewAuthService(db *gorm.DB, cfg config.Config, plans *PlanService) *AuthService {
	return &AuthService{db: db, cfg: cfg, plans: plans}
}

type Claims struct {
	UserID   uint64 `json:"user_id"`
	Username string `json:"username"`
	Role     string `json:"role"`
	PlanID   string `json:"plan_id"`
	jwt.RegisteredClaims
}

func (c *Claims) IsAdmin() bool { return c.Role == model.RoleAdmin }

type AuthResult struct {
	Token string     `json:"token"`
	User  *model.User `json:"user"`
	Plan  Plan       `json:"plan"`
}

func (s *AuthService) Register(username, password string) (*AuthResult, error) {
	username = strings.TrimSpace(username)
	if utf8.RuneCountInString(username) < 3 || utf8.RuneCountInString(username) > 32 {
		return nil, ErrInvalidUsername
	}
	if len(password) < 6 {
		return nil, ErrWeakPassword
	}
	var count int64
	if err := s.db.Model(&model.User{}).Where("username = ?", username).Count(&count).Error; err != nil {
		return nil, err
	}
	if count > 0 {
		return nil, ErrUserExists
	}
	hash, err := bcrypt.GenerateFromPassword([]byte(password), bcrypt.DefaultCost)
	if err != nil {
		return nil, err
	}
	now := time.Now()
	planID := "free"
	if s.plans != nil {
		cfg := s.plans.Get()
		if cfg.DefaultPlanID != "" {
			planID = cfg.DefaultPlanID
		}
	}
	user := model.User{
		Username: username, PasswordHash: string(hash),
		Role: model.RoleUser, PlanID: planID, Status: model.UserStatusActive,
		CreatedAt: &now, UpdatedAt: &now,
	}
	if err := s.db.Create(&user).Error; err != nil {
		return nil, err
	}
	return s.issue(&user)
}

func (s *AuthService) Login(username, password string) (*AuthResult, error) {
	var user model.User
	err := s.db.Where("username = ?", strings.TrimSpace(username)).First(&user).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, ErrAuthFailed
	}
	if err != nil {
		return nil, err
	}
	if user.Status != model.UserStatusActive {
		return nil, ErrUserDisabled
	}
	if bcrypt.CompareHashAndPassword([]byte(user.PasswordHash), []byte(password)) != nil {
		return nil, ErrAuthFailed
	}
	return s.issue(&user)
}

func (s *AuthService) issue(user *model.User) (*AuthResult, error) {
	claims := Claims{
		UserID: user.ID, Username: user.Username, Role: user.Role, PlanID: user.PlanID,
		RegisteredClaims: jwt.RegisteredClaims{
			ExpiresAt: jwt.NewNumericDate(time.Now().Add(72 * time.Hour)),
			IssuedAt:  jwt.NewNumericDate(time.Now()),
		},
	}
	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	signed, err := token.SignedString([]byte(s.cfg.JWTSecret))
	if err != nil {
		return nil, err
	}
	plan, _ := s.plans.GetPlan(user.PlanID)
	u := *user
	return &AuthResult{Token: signed, User: &u, Plan: plan}, nil
}

func (s *AuthService) Parse(tokenStr string) (*Claims, error) {
	token, err := jwt.ParseWithClaims(tokenStr, &Claims{}, func(t *jwt.Token) (interface{}, error) {
		return []byte(s.cfg.JWTSecret), nil
	})
	if err != nil {
		return nil, err
	}
	claims, ok := token.Claims.(*Claims)
	if !ok || !token.Valid {
		return nil, errors.New("invalid token")
	}
	return claims, nil
}

func (s *AuthService) GetUser(id uint64) (*model.User, error) {
	var user model.User
	err := s.db.Where("id = ?", id).First(&user).Error
	if errors.Is(err, gorm.ErrRecordNotFound) {
		return nil, ErrNotFound
	}
	return &user, err
}

type ListUsersQuery struct {
	Page     int
	PageSize int
	Keyword  string
}

func (s *AuthService) ListUsers(q ListUsersQuery) (int64, []model.User, error) {
	if q.Page < 1 {
		q.Page = 1
	}
	if q.PageSize < 1 || q.PageSize > 100 {
		q.PageSize = 20
	}
	tx := s.db.Model(&model.User{})
	if kw := strings.TrimSpace(q.Keyword); kw != "" {
		tx = tx.Where("username LIKE ?", "%"+kw+"%")
	}
	var total int64
	if err := tx.Count(&total).Error; err != nil {
		return 0, nil, err
	}
	var items []model.User
	err := tx.Order("id DESC").Offset((q.Page - 1) * q.PageSize).Limit(q.PageSize).Find(&items).Error
	return total, items, err
}

func (s *AuthService) SetUserPlan(userID uint64, planID string) error {
	if _, ok := s.plans.GetPlan(planID); !ok {
		return errors.New("plan not found")
	}
	now := time.Now()
	res := s.db.Model(&model.User{}).Where("id = ?", userID).Updates(map[string]interface{}{
		"plan_id": planID, "updated_at": &now,
	})
	if res.Error != nil {
		return res.Error
	}
	if res.RowsAffected == 0 {
		return ErrNotFound
	}
	return nil
}

func (s *AuthService) SetUserStatus(userID uint64, status int) error {
	now := time.Now()
	res := s.db.Model(&model.User{}).Where("id = ?", userID).Updates(map[string]interface{}{
		"status": status, "updated_at": &now,
	})
	if res.Error != nil {
		return res.Error
	}
	if res.RowsAffected == 0 {
		return ErrNotFound
	}
	return nil
}
