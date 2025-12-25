package config

import (
	"fmt"
	"os"
	"time"

	"gopkg.in/yaml.v3"
)

type Config struct {
	Server      ServerConfig      `yaml:"server"`
	Laravel     LaravelConfig     `yaml:"laravel"`
	Redis       RedisConfig       `yaml:"redis"`
	Cache       CacheConfig       `yaml:"cache"`
	Logging     LoggingConfig     `yaml:"logging"`
	Metrics     MetricsConfig     `yaml:"metrics"`
	RateLimiting RateLimitingConfig `yaml:"rate_limiting"`
	Origins     map[string]string `yaml:"origins"`
}

type ServerConfig struct {
	Host         string        `yaml:"host"`
	Port         int           `yaml:"port"`
	ReadTimeout  time.Duration `yaml:"read_timeout"`
	WriteTimeout time.Duration `yaml:"write_timeout"`
}

type LaravelConfig struct {
	BaseURL      string        `yaml:"base_url"`
	APIKey       string        `yaml:"api_key"`
	Timeout      time.Duration `yaml:"timeout"`
	RetryAttempts int          `yaml:"retry_attempts"`
	RetryDelay   time.Duration `yaml:"retry_delay"`
}

type RedisConfig struct {
	Host        string `yaml:"host"`
	Password    string `yaml:"password"`
	DB          int    `yaml:"db"`
	PoolSize    int    `yaml:"pool_size"`
	MinIdleConns int   `yaml:"min_idle_conns"`
}

type CacheConfig struct {
	ManifestTTL    time.Duration `yaml:"manifest_ttl"`
	AdDecisionTTL  time.Duration `yaml:"ad_decision_ttl"`
	VASTTTL        time.Duration `yaml:"vast_ttl"`
}

type LoggingConfig struct {
	Level  string `yaml:"level"`
	Format string `yaml:"format"`
}

type MetricsConfig struct {
	Enabled bool   `yaml:"enabled"`
	Path    string `yaml:"path"`
}

type RateLimitingConfig struct {
	Enabled           bool `yaml:"enabled"`
	RequestsPerMinute int  `yaml:"requests_per_minute"`
}

var GlobalConfig *Config

func LoadConfig(path string) (*Config, error) {
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, fmt.Errorf("failed to read config file: %w", err)
	}

	var config Config
	if err := yaml.Unmarshal(data, &config); err != nil {
		return nil, fmt.Errorf("failed to parse config: %w", err)
	}

	GlobalConfig = &config
	return &config, nil
}

func GetConfig() *Config {
	if GlobalConfig == nil {
		// Return default config
		return &Config{
			Server: ServerConfig{
				Host: "0.0.0.0",
				Port: 8080,
			},
			Laravel: LaravelConfig{
				BaseURL: "http://localhost:8000/api/v1",
				Timeout: 5 * time.Second,
			},
		}
	}
	return GlobalConfig
}

